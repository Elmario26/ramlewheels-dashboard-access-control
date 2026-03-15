<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLoggerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private Security $security,
    ) {}

    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $affectedData = null
    ): ActivityLog {
        $log = new ActivityLog();
        
        // Set user (may be null for system actions)
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
        }

        // Set action
        $log->setAction($action);

        // Set entity information
        if ($entityType) {
            $log->setEntityType($entityType);
        }
        if ($entityId) {
            $log->setEntityId($entityId);
        }

        // Set description
        if ($description) {
            $log->setDescription($description);
        }

        // Set affected data
        if ($affectedData) {
            $log->setAffectedData($affectedData);
        }

        // Set request information
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent', ''));
        }

        // Set timestamp
        $log->setCreatedAt(new \DateTime());

        // Persist to database
        $this->em->persist($log);
        $this->em->flush();

        return $log;
    }

    public function logCreate(string $entityType, int $entityId, ?string $description = null, ?array $data = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_CREATE,
            $entityType,
            $entityId,
            $description ?? "Created {$entityType} #{$entityId}",
            $data
        );
    }

    public function logUpdate(string $entityType, int $entityId, ?string $description = null, ?array $changes = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_UPDATE,
            $entityType,
            $entityId,
            $description ?? "Updated {$entityType} #{$entityId}",
            $changes
        );
    }

    public function logDelete(string $entityType, int $entityId, ?string $description = null, ?array $data = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_DELETE,
            $entityType,
            $entityId,
            $description ?? "Deleted {$entityType} #{$entityId}",
            $data
        );
    }

    public function logView(string $entityType, int $entityId, ?string $description = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_VIEW,
            $entityType,
            $entityId,
            $description ?? "Viewed {$entityType} #{$entityId}"
        );
    }

    public function logLogin(?string $description = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_LOGIN,
            description: $description ?? "User logged in"
        );
    }

    public function logLogout(?string $description = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_LOGOUT,
            description: $description ?? "User logged out"
        );
    }
}
