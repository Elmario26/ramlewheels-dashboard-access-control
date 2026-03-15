<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Find documents by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.documentType = :type')
            ->andWhere('d.isLatestVersion = true')
            ->setParameter('type', $type)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by related entity
     */
    public function findByRelatedEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.relatedEntityType = :entityType')
            ->andWhere('d.relatedEntityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search documents
     */
    public function searchDocuments(string $query): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isLatestVersion = true')
            ->andWhere('(d.fileName LIKE :query OR d.documentType LIKE :query OR d.description LIKE :query)')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent documents
     */
    public function getRecentDocuments(int $limit = 50): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isLatestVersion = true')
            ->orderBy('d.uploadedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get documents grouped by type
     */
    public function getDocumentsByType(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.documentType, COUNT(d.id) as count')
            ->where('d.isLatestVersion = true')
            ->groupBy('d.documentType')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.category = :category')
            ->andWhere('d.isLatestVersion = true')
            ->setParameter('category', $category)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by vehicle
     */
    public function findByVehicle(int $vehicleId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.relatedEntityType = :entityType')
            ->andWhere('d.relatedEntityId = :entityId')
            ->andWhere('d.isLatestVersion = true')
            ->setParameter('entityType', 'Cars')
            ->setParameter('entityId', $vehicleId)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by customer
     */
    public function findByCustomer(int $customerId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.relatedEntityType = :entityType')
            ->andWhere('d.relatedEntityId = :entityId')
            ->andWhere('d.isLatestVersion = true')
            ->setParameter('entityType', 'Customer')
            ->setParameter('entityId', $customerId)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get document versions
     */
    public function getDocumentVersions(int $documentId): array
    {
        $document = $this->find($documentId);
        if (!$document) {
            return [];
        }

        $parentId = $document->getParentDocument() ? $document->getParentDocument()->getId() : $documentId;
        $parentDoc = $this->find($parentId);

        return $this->createQueryBuilder('d')
            ->where('d.id = :parentId')
            ->orWhere('d.parentDocument = :parentDoc')
            ->setParameter('parentId', $parentId)
            ->setParameter('parentDoc', $parentDoc)
            ->orderBy('d.version', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filter documents with multiple criteria
     */
    public function filterDocuments(array $filters): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.isLatestVersion = true');

        if (!empty($filters['category'])) {
            $qb->andWhere('d.category = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('d.documentType = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['vehicle_id'])) {
            $qb->andWhere('d.relatedEntityType = :vehicleType')
               ->andWhere('d.relatedEntityId = :vehicleId')
               ->setParameter('vehicleType', 'Cars')
               ->setParameter('vehicleId', $filters['vehicle_id']);
        }

        if (!empty($filters['customer_id'])) {
            $qb->andWhere('d.relatedEntityType = :customerType')
               ->andWhere('d.relatedEntityId = :customerId')
               ->setParameter('customerType', 'Customer')
               ->setParameter('customerId', $filters['customer_id']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('d.uploadedAt >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('d.uploadedAt <= :dateTo')
               ->setParameter('dateTo', $filters['date_to']);
        }

        return $qb->orderBy('d.uploadedAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Get documents grouped by category
     */
    public function getDocumentsByCategory(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.category, COUNT(d.id) as count')
            ->where('d.isLatestVersion = true')
            ->groupBy('d.category')
            ->getQuery()
            ->getResult();
    }
}

