<?php

namespace App\Command;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-railway-seed',
    description: 'Import data/railway_seed.json into the database (Railway deploy, runs when IMPORT_RAILWAY_SEED=1)',
)]
final class ImportRailwaySeedCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $this->projectDir . '/data/railway_seed.json';

        if (!is_readable($path)) {
            $io->warning('No data/railway_seed.json — skip seed import.');

            return Command::SUCCESS;
        }

        if ($this->userRepository->count([]) > 0) {
            $io->note('Users already exist; seed import skipped.');

            return Command::SUCCESS;
        }

        /** @var array{users?: list<array<string, mixed>>, customers?: list<array<string, mixed>>} $payload */
        $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $userCount = 0;
        foreach ($payload['users'] ?? [] as $row) {
            $user = new User();
            $user->setUsername((string) $row['username']);
            $user->setEmail($row['email'] ?? null);
            $user->setPassword((string) $row['password']);
            $user->setFirstName((string) $row['firstName']);
            $user->setLastName((string) $row['lastName']);
            $user->setPhone($row['phone'] ?? null);
            $user->setRole((string) $row['role']);
            $user->setStatus((string) ($row['status'] ?? 'active'));
            $user->setNotes($row['notes'] ?? null);
            $user->setIsVerified((bool) ($row['isVerified'] ?? true));
            $user->setVerificationToken($row['verificationToken'] ?? null);
            if (!empty($row['createdAt'])) {
                $user->setCreatedAt(new \DateTime((string) $row['createdAt']));
            }
            $this->entityManager->persist($user);
            ++$userCount;
        }

        $customerCount = 0;
        foreach ($payload['customers'] ?? [] as $row) {
            if ($row['email'] && $this->customerRepository->findByEmail((string) $row['email'])) {
                continue;
            }
            $customer = new Customer();
            $customer->setFirstName((string) $row['firstName']);
            $customer->setLastName((string) $row['lastName']);
            $customer->setEmail($row['email'] ?? null);
            $customer->setPhone($row['phone'] ?? null);
            $customer->setAddress($row['address'] ?? null);
            $customer->setCity($row['city'] ?? null);
            $customer->setZipCode($row['zipCode'] ?? null);
            $customer->setNotes($row['notes'] ?? null);
            if (!empty($row['createdAt'])) {
                $customer->setCreatedAt(new \DateTime((string) $row['createdAt']));
            }
            $this->entityManager->persist($customer);
            ++$customerCount;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Imported %d users and %d customers from railway_seed.json.', $userCount, $customerCount));

        return Command::SUCCESS;
    }
}
