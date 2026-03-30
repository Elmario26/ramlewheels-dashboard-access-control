<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

use App\Service\EmailVerificationService; // NEW - Inject email service
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; // NEW - For generating absolute URLs

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        EmailVerificationService $emailVerificationService // ADDED
        ): Response {
        // Log POST request headers and body for debugging 422 responses
        if ($request->isMethod('POST')) {
            $logger->debug('Registration POST headers', array_map(function($v){ return implode(', ', (array) $v); }, $request->headers->all()));
            $content = $request->getContent();
            $logger->debug('Registration POST content', ['content' => $content]);
        }
        // default new instances to staff before validation
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Log form submission state and validation errors for debugging
        $isSubmitted = $form->isSubmitted();
        $logger->debug('Registration form submitted? ' . ($isSubmitted ? 'yes' : 'no'));
        if ($isSubmitted) {
            $isValid = $form->isValid();
            $logger->debug('Registration form valid? ' . ($isValid ? 'yes' : 'no'));
            if (!$isValid) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $logger->debug('Registration form errors', ['errors' => $errors]);
            }
        } else {
            $logger->debug('Registration form valid? not-submitted');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);
            
            // Generate verification token and set it in the user
            $entityManager->persist($user);
            try {
                $entityManager->flush();
            } catch (\Exception $e) {
                // Surface DB errors on the form so they appear in the template for debugging
                $form->addError(new FormError('Registration failed: ' . $e->getMessage()));

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

             // Generate verification URL
            $verificationUrl = $this->generateUrl(
            'app_verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Send verification email
            $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
            $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');
            $verificationUrl = $this->generateUrl('app_verify_email', ['token' => $verificationToken], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->redirectToRoute('app_login');

           
    

            // Send verification email
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
