<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\CustomerAccountService;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/register', name: 'api_register', methods: ['POST'])]
final class RegisterController extends AbstractController
{
    public function __invoke(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        EmailVerificationService $emailVerificationService,
        CustomerAccountService $customerAccountService,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], 400);
            }

            // Validate required fields
            $required = ['fullName', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->json(['error' => "Field '$field' is required"], 400);
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Invalid email format'], 400);
            }

            // Validate password length
            if (strlen($data['password']) < 6) {
                return $this->json(['error' => 'Password must be at least 6 characters'], 400);
            }

            // Parse fullName into firstName and lastName
            $nameParts = explode(' ', trim($data['fullName']), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Check if user already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $data['email']
            ]);

            if ($existingUser) {
                return $this->json(['error' => 'Email already registered'], 409);
            }

            // Create new user
            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['email']); // Use email as username
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRole('customer');

            // Encode password
            $hashedPassword = $userPasswordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Generate verification token
            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);

            // Validate user
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], 400);
            }

            // Save user and mirror into CRM customers list
            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $customerAccountService->ensureCustomerRecord($user);
                $entityManager->flush();
            } catch (\Throwable $crmError) {
                $logger->warning('Customer CRM sync skipped after registration', [
                    'email' => $user->getEmail(),
                    'error' => $crmError->getMessage(),
                ]);
            }

            $logger->info('New user registered', ['email' => $user->getEmail()]);

            return $this->json([
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account.',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFirstName() . ' ' . $user->getLastName(),
                ]
            ], 201);

        } catch (\Exception $e) {
            $logger->error('Registration error', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }
}
