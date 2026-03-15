<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function findByFilters(
        ?int $userId = null,
        ?string $action = null,
        ?\DateTime $dateFrom = null,
        ?\DateTime $dateTo = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($userId) {
            $qb->andWhere('a.user = :userId')
                ->setParameter('userId', $userId);
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $qb->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByFilters(
        ?int $userId = null,
        ?string $action = null,
        ?\DateTime $dateFrom = null,
        ?\DateTime $dateTo = null
    ): int {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        if ($userId) {
            $qb->andWhere('a.user = :userId')
                ->setParameter('userId', $userId);
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $qb->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->findBy(['user' => $userId], ['createdAt' => 'DESC'], $limit);
    }

    public function findByAction(string $action, int $limit = 50): array
    {
        return $this->findBy(['action' => $action], ['createdAt' => 'DESC'], $limit);
    }

    public function findRecentLogs(int $days = 7, int $limit = 100): array
    {
        $dateFrom = (new \DateTime())->modify("-{$days} days");

        return $this->createQueryBuilder('a')
            ->andWhere('a.createdAt >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
