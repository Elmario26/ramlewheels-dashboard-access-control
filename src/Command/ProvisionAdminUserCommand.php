<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:provision-admin',
    description: 'Create the first admin user when ADMIN_EMAIL and ADMIN_PASSWORD are set (e.g. Railway deploy)',
)]
final class ProvisionAdminUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = trim((string) ($_SERVER['ADMIN_EMAIL'] ?? $_ENV['ADMIN_EMAIL'] ?? ''));
        $password = (string) ($_SERVER['ADMIN_PASSWORD'] ?? $_ENV['ADMIN_PASSWORD'] ?? '');
        $username = trim((string) ($_SERVER['ADMIN_USERNAME'] ?? $_ENV['ADMIN_USERNAME'] ?? ''));
        $firstName = trim((string) ($_SERVER['ADMIN_FIRST_NAME'] ?? $_ENV['ADMIN_FIRST_NAME'] ?? 'Admin'));
        $lastName = trim((string) ($_SERVER['ADMIN_LAST_NAME'] ?? $_ENV['ADMIN_LAST_NAME'] ?? 'User'));

        if ($email === '' || $password === '') {
            $io->note('Skipped admin provisioning (set ADMIN_EMAIL and ADMIN_PASSWORD to create the first admin on deploy).');

            return Command::SUCCESS;
        }

        if ($this->userRepository->count([]) > 0) {
            $io->note('Users already exist; admin provisioning skipped.');

            return Command::SUCCESS;
        }

        if ($username === '') {
            $username = strstr($email, '@', true) ?: $email;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRole('admin');
        $user->setStatus('active');
        $user->setIsVerified(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin user created: %s (username: %s)', $email, $username));

        return Command::SUCCESS;
    }
}
