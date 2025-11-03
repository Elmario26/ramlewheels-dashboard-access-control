<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * Search customers by name, email, or phone
     */
    public function searchCustomers(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.firstName LIKE :query OR c.lastName LIKE :query OR c.email LIKE :query OR c.phone LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent customers
     */
    public function getRecentCustomers(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top customers by purchase amount
     */
    public function getTopCustomers(int $limit = 10): array
    {
        try {
            return $this->createQueryBuilder('c')
                ->leftJoin('c.sales', 's')
                ->where('s.status = :status')
                ->setParameter('status', 'completed')
                ->groupBy('c.id')
                ->orderBy('SUM(s.salePrice)', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStatistics(): array
    {
        $totalCustomers = $this->count([]);
        
        try {
            $newThisMonth = $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.createdAt >= :startOfMonth')
                ->setParameter('startOfMonth', new \DateTime('first day of this month'))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $newThisMonth = 0;
        }

        try {
            $customersWithPurchases = $this->createQueryBuilder('c')
                ->innerJoin('c.sales', 's')
                ->where('s.status = :status')
                ->setParameter('status', 'completed')
                ->select('COUNT(DISTINCT c.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $customersWithPurchases = 0;
        }

        return [
            'total_customers' => $totalCustomers,
            'new_this_month' => $newThisMonth,
            'customers_with_purchases' => $customersWithPurchases,
            'conversion_rate' => $totalCustomers > 0 ? round(($customersWithPurchases / $totalCustomers) * 100, 1) : 0,
        ];
    }

    /**
     * Find customers by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->where('c.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get customers with recent activity
     */
    public function getCustomersWithRecentActivity(int $days = 30): array
    {
        try {
            $date = new \DateTime("-$days days");
            
            return $this->createQueryBuilder('c')
                ->leftJoin('c.sales', 's')
                ->where('s.createdAt >= :date OR c.updatedAt >= :date')
                ->setParameter('date', $date)
                ->orderBy('COALESCE(s.createdAt, c.updatedAt)', 'DESC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
}
