<?php

namespace App\EventListener;

use App\Service\ActivityLoggerService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
final class LogoutEventListener
{
    public function __construct(private ActivityLoggerService $activityLogger) {}

    public function __invoke(LogoutEvent $event): void
    {
        $this->activityLogger->logLogout();
    }
}
