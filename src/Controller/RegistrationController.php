<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
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

            // do anything else you need here, like send an email

            $response = $security->login($user, 'App\\Security\\LoginAuthenticator', 'main');
            if ($response instanceof Response) {
                return $response;
            }

            // fallback: redirect to dashboard
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
