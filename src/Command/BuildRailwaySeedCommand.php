<?php

namespace App\Command;

use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:build-railway-seed',
    description: 'Export users (and customers) from local DB to data/railway_seed.json for free Railway deploy',
)]
final class BuildRailwaySeedCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (($this->getApplication()?->getKernel()->getEnvironment() ?? '') !== 'dev') {
            $io->error('Run this only in dev (XAMPP): php bin/console app:build-railway-seed');

            return Command::FAILURE;
        }

        $users = [];
        foreach ($this->userRepository->findAll() as $user) {
            $users[] = [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'password' => $user->getPassword(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'role' => $user->getRole(),
                'status' => $user->getStatus(),
                'notes' => $user->getNotes(),
                'isVerified' => $user->isVerified(),
                'verificationToken' => $user->getVerificationToken(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        $customers = [];
        foreach ($this->customerRepository->findAll() as $customer) {
            $customers[] = [
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone(),
                'address' => $customer->getAddress(),
                'city' => $customer->getCity(),
                'zipCode' => $customer->getZipCode(),
                'notes' => $customer->getNotes(),
                'createdAt' => $customer->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        $payload = [
            'exportedAt' => (new \DateTime())->format('c'),
            'users' => $users,
            'customers' => $customers,
        ];

        $path = $this->projectDir . '/data/railway_seed.json';
        (new Filesystem())->mkdir(\dirname($path));
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $io->success(sprintf('Wrote %s (%d users, %d customers). Commit this file and redeploy Railway.', $path, \count($users), \count($customers)));
        $io->note('On Railway set: IMPORT_RAILWAY_SEED=1 (once), then remove it after first successful deploy.');

        return Command::SUCCESS;
    }
}
