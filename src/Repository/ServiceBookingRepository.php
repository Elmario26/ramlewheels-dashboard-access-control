<?php

namespace App\Repository;

use App\Entity\ServiceBooking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceBooking>
 */
class ServiceBookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceBooking::class);
    }

    /**
     * @return ServiceBooking[]
     */
    public function findByCustomer(int $customerId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('s.requestedDateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ServiceBooking[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.requestedDateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ServiceBooking[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('s.requestedDateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
