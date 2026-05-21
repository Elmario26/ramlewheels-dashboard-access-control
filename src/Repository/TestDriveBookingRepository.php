<?php

namespace App\Repository;

use App\Entity\TestDriveBooking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TestDriveBooking>
 */
class TestDriveBookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestDriveBooking::class);
    }

    public function findByCustomer($customerId)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('t.requestedDateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPending()
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('t.requestedDateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus($status)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.requestedDateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
