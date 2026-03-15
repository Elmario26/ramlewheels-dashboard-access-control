<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\Log\LoggerInterface;

class MechanicAccessRedirectListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 5],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Check if it's an access denied exception (multiple types)
        if (!($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException)) {
            return;
        }

        // Get the current token and user
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user || is_string($user)) {
            return;
        }

        // Get the user's roles
        $userRoles = $token->getRoleNames();

        // If user has ROLE_MECHANIC, redirect to services
        if (in_array('ROLE_MECHANIC', $userRoles)) {
            $this->logger->info('Redirecting mechanic from denied route to /services');
            $response = new RedirectResponse('/services');
            $event->setResponse($response);
        }
    }
}
