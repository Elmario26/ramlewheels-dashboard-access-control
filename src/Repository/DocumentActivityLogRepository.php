<?php

namespace App\Repository;

use App\Entity\DocumentActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentActivityLog>
 */
class DocumentActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentActivityLog::class);
    }

    /**
     * Find logs by document
     */
    public function findByDocument(int $documentId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.document = :documentId')
            ->setParameter('documentId', $documentId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent activity
     */
    public function findRecentActivity(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

