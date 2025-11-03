<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function save(Service $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Service $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find services by customer
     */
    public function findByCustomer(int $customerId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('s.serviceDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find services by vehicle
     */
    public function findByVehicle(int $vehicleId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.vehicle = :vehicleId')
            ->setParameter('vehicleId', $vehicleId)
            ->orderBy('s.serviceDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find services by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.serviceDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get service statistics
     */
    public function getServiceStatistics(): array
    {
        $qb = $this->createQueryBuilder('s');
        
        $totalServices = $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $completedServices = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        $pendingServices = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        $inProgressServices = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'in_progress')
            ->getQuery()
            ->getSingleScalarResult();

        $totalRevenue = $this->createQueryBuilder('s')
            ->select('SUM(s.cost)')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $thisMonthServices = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.createdAt >= :startOfMonth')
            ->setParameter('startOfMonth', new \DateTime('first day of this month'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_services' => (int) $totalServices,
            'completed_services' => (int) $completedServices,
            'pending_services' => (int) $pendingServices,
            'in_progress_services' => (int) $inProgressServices,
            'total_revenue' => (float) $totalRevenue,
            'this_month_services' => (int) $thisMonthServices,
            'completion_rate' => $totalServices > 0 ? round(($completedServices / $totalServices) * 100, 1) : 0
        ];
    }

    /**
     * Search services by query
     */
    public function searchServices(string $query): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.customer', 'c')
            ->leftJoin('s.vehicle', 'v')
            ->andWhere('s.serviceType LIKE :query OR s.description LIKE :query OR c.firstName LIKE :query OR c.lastName LIKE :query OR v.brand LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.serviceDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent services
     */
    public function findRecentServices(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get services by date range
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.serviceDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.serviceDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
