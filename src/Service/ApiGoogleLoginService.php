<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Mobile / API Google sign-in: verify idToken, find or create user, return Lexik JWT.
 */
final class ApiGoogleLoginService
{
    public function __construct(
        private GoogleIdTokenVerifier $googleIdTokenVerifier,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private CustomerAccountService $customerAccountService,
        private ?ActivityLoggerService $activityLogger = null,
    ) {
    }

    /**
     * @return array{token: string}
     */
    public function login(string $idToken): array
    {
        $payload = $this->googleIdTokenVerifier->verify($idToken);
        $email = (string) $payload['email'];

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            if (!$user->isEnabled()) {
                throw new \RuntimeException('Your account is suspended or inactive. Please contact an administrator.');
            }
            $user->setLastLoginAt(new \DateTime());
        } else {
            $user = $this->createUserFromGooglePayload($payload);
            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        if ($user->getRole() === 'customer') {
            $this->customerAccountService->ensureCustomerRecord($user);
            $this->entityManager->flush();
        }

        if ($this->activityLogger !== null) {
            $this->activityLogger->logLogin();
        }

        return ['token' => $this->jwtManager->create($user)];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createUserFromGooglePayload(array $payload): User
    {
        $email = (string) $payload['email'];
        $firstName = (string) ($payload['given_name'] ?? '');
        $lastName = (string) ($payload['family_name'] ?? '');

        if ($firstName === '' && isset($payload['name']) && is_string($payload['name'])) {
            $parts = explode(' ', trim($payload['name']), 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
        }

        if ($firstName === '') {
            $firstName = 'Google';
        }
        if ($lastName === '') {
            $lastName = 'User';
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($this->generateUniqueUsername($firstName, $lastName, $email));
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRole('customer');
        $user->setIsVerified(true);
        $user->setStatus('active');
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
        $user->setLastLoginAt(new \DateTime());

        return $user;
    }

    private function generateUniqueUsername(string $firstName, string $lastName, string $email): string
    {
        $base = strtolower($firstName . $lastName);
        $base = preg_replace('/[^a-z0-9]/', '', $base) ?? '';
        if ($base === '') {
            $base = strstr($email, '@', true) ?: 'user';
            $base = preg_replace('/[^a-z0-9]/', '', strtolower($base)) ?? 'user';
        }

        $username = $base;
        $counter = 1;

        while ($this->userRepository->findOneBy(['username' => $username]) instanceof User) {
            $username = $base . $counter;
            ++$counter;
        }

        return $username;
    }
}
