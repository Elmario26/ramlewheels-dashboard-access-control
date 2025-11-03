<?php

namespace App\Repository;

use App\Entity\Sales;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sales>
 */
class SalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sales::class);
    }

    public function save(Sales $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Sales $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get total sales revenue
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.salePrice) as total')
            ->where('s.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Get monthly sales revenue
     */
    public function getMonthlyRevenue(int $year = null, int $month = null): float
    {
        $qb = $this->createQueryBuilder('s')
            ->select('SUM(s.salePrice) as total')
            ->where('s.status = :status')
            ->setParameter('status', 'completed');

        if ($year && $month) {
            $startDate = new \DateTime("$year-$month-01");
            $endDate = new \DateTime("$year-$month-01");
            $endDate->modify('last day of this month')->setTime(23, 59, 59);
            
            $qb->andWhere('s.saleDate >= :startDate')
               ->andWhere('s.saleDate <= :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        } elseif ($year) {
            $startDate = new \DateTime("$year-01-01");
            $endDate = new \DateTime("$year-12-31 23:59:59");
            
            $qb->andWhere('s.saleDate >= :startDate')
               ->andWhere('s.saleDate <= :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return (float)($result ?? 0);
    }

    /**
     * Get sales count by status
     */
    public function getCountByStatus(): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('s.status, COUNT(s.id) as count')
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        $statusCounts = [];
        foreach ($results as $result) {
            $statusCounts[$result['status']] = (int)$result['count'];
        }

        return $statusCounts;
    }

    /**
     * Get recent sales
     */
    public function getRecentSales(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.vehicle', 'v')
            ->addSelect('v')
            ->orderBy('s.saleDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sales by date range
     */

    /**
     * Get top selling vehicles
     */
    public function getTopSellingVehicles(int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->select('v.brand, v.year, COUNT(s.id) as sales_count, SUM(s.salePrice) as total_revenue')
            ->leftJoin('s.vehicle', 'v')
            ->where('s.status = :status')
            ->setParameter('status', 'completed')
            ->groupBy('v.brand, v.year')
            ->orderBy('sales_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sales statistics for dashboard
     */
    public function getSalesStatistics(): array
    {
        $totalRevenue = $this->getTotalRevenue();
        $thisMonthRevenue = $this->getMonthlyRevenue((int)date('Y'), (int)date('n'));
        $lastMonthRevenue = $this->getMonthlyRevenue((int)date('Y'), (int)date('n') - 1);
        
        $revenueGrowth = 0;
        if ($lastMonthRevenue > 0) {
            $revenueGrowth = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        }

        $totalSales = $this->count(['status' => 'completed']);
        $pendingSales = $this->count(['status' => 'pending']);
        $totalTransactions = $totalSales + $pendingSales;

        // Calculate average sale price
        $averageSalePrice = 0;
        if ($totalSales > 0) {
            $averageSalePrice = $totalRevenue / $totalSales;
        }

        // Calculate conversion rate
        $conversionRate = 0;
        if ($totalTransactions > 0) {
            $conversionRate = ($totalSales / $totalTransactions) * 100;
        }

        // Get top selling brand
        $topBrand = $this->getTopSellingBrand();

        return [
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $thisMonthRevenue,
            'revenue_growth' => $revenueGrowth,
            'total_sales' => $totalSales,
            'pending_sales' => $pendingSales,
            'average_sale_price' => $averageSalePrice,
            'conversion_rate' => $conversionRate,
            'top_brand' => $topBrand,
        ];
    }

    /**
     * Get top selling brand
     */
    public function getTopSellingBrand(): ?string
    {
        $result = $this->createQueryBuilder('s')
            ->select('v.brand, COUNT(s.id) as sales_count')
            ->leftJoin('s.vehicle', 'v')
            ->where('s.status = :status')
            ->setParameter('status', 'completed')
            ->groupBy('v.brand')
            ->orderBy('sales_count', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['brand'] : null;
    }

    /**
     * Search sales by customer, vehicle, or sale ID
     */
    public function searchSales(string $query): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s', 'c', 'v')  // Explicitly select all fields
            ->leftJoin('s.customer', 'c')
            ->leftJoin('s.vehicle', 'v')
            ->orderBy('s.saleDate', 'DESC');

        $orX = $qb->expr()->orX();

        // Search in customer fields - using LOWER() for case-insensitive comparison
        $orX->add($qb->expr()->like('LOWER(c.firstName)', ':query'))
            ->add($qb->expr()->like('LOWER(c.lastName)', ':query'))
            ->add($qb->expr()->like('LOWER(c.email)', ':query'))
            ->add($qb->expr()->like('LOWER(c.phone)', ':query'))
            ->add($qb->expr()->like('LOWER(CONCAT(c.firstName, \' \', c.lastName))', ':query'));

        // Search in vehicle fields - using LOWER() for case-insensitive comparison
        $orX->add($qb->expr()->like('LOWER(v.brand)', ':query'))
            ->add($qb->expr()->like('LOWER(v.make)', ':query'))
            ->add($qb->expr()->like('LOWER(v.color)', ':query'));

        // Search in sale fields - using LOWER() for case-insensitive comparison
        $orX->add($qb->expr()->like('LOWER(s.paymentMethod)', ':query'));

        // Add numeric field searches with separate parameters
        $orX->add($qb->expr()->like('LOWER(v.Year)', ':query'))
            ->add($qb->expr()->eq('s.id', ':idQuery'))
            ->add($qb->expr()->eq('s.salePrice', ':priceQuery'));

        $qb->where($orX)
           ->setParameter('query', '%' . strtolower($query) . '%')
           ->setParameter('idQuery', is_numeric($query) ? (int)$query : 0)
           ->setParameter('priceQuery', is_numeric($query) ? (float)$query : 0);

        // Store the original search term for logging
        $searchTerm = $query;
        $queryBuilder = $qb->getQuery();
        
        // Debug the actual SQL being executed
        error_log('Search Query Debug ----------------');
        error_log('Search Term: ' . $searchTerm);
        error_log('Generated SQL: ' . $queryBuilder->getSQL());
        error_log('Parameters: ' . json_encode($queryBuilder->getParameters()->toArray()));
        
        try {
            // Execute query and get results
            $results = $queryBuilder->getResult();
            error_log('Results count: ' . count($results));
            
            // Debug first few results if any
            if (count($results) > 0) {
                foreach (array_slice($results, 0, 3) as $result) {
                    error_log(sprintf(
                        'Result Debug - Sale ID: %d, Vehicle Brand: %s, Customer: %s',
                        $result->getId(),
                        $result->getVehicle() ? $result->getVehicle()->getBrand() : 'N/A',
                        $result->getCustomer() ? $result->getCustomer()->getFirstName() . ' ' . $result->getCustomer()->getLastName() : 'N/A'
                    ));
                }
            }
            
            return $results;
        } catch (\Exception $e) {
            error_log('Search Query Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get filtered sales based on criteria
     */
    public function getFilteredSales(array $filters): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.customer', 'c')
            ->leftJoin('s.vehicle', 'v')
            ->addSelect('c', 'v')
            ->orderBy('s.saleDate', 'DESC');

        if (!empty($filters['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('s.saleDate >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $endDate = new \DateTime($filters['date_to']);
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('s.saleDate <= :dateTo')
               ->setParameter('dateTo', $endDate);
        }

        if (!empty($filters['payment_method'])) {
            $qb->andWhere('s.paymentMethod = :paymentMethod')
               ->setParameter('paymentMethod', $filters['payment_method']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get monthly sales data for charts
     */
    public function getMonthlySalesData(int $year = null): array
    {
        $year = $year ?? (int)date('Y');
        
        $startDate = new \DateTime("$year-01-01");
        $endDate = new \DateTime("$year-12-31 23:59:59");
        
        // Get all sales for the year and group by month in PHP
        $sales = $this->createQueryBuilder('s')
            ->where('s.saleDate >= :startDate')
            ->andWhere('s.saleDate <= :endDate')
            ->andWhere('s.status = :status')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($sales as $sale) {
            $month = (int)$sale->getSaleDate()->format('n');
            if (!isset($results[$month])) {
                $results[$month] = [
                    'month' => $month,
                    'sales_count' => 0,
                    'revenue' => 0
                ];
            }
            $results[$month]['sales_count']++;
            $results[$month]['revenue'] += (float)$sale->getSalePrice();
        }

        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = [
                'sales_count' => 0,
                'revenue' => 0
            ];
        }

        foreach ($results as $result) {
            $monthlyData[(int)$result['month']] = [
                'sales_count' => (int)$result['sales_count'],
                'revenue' => (float)$result['revenue']
            ];
        }

        return $monthlyData;
    }
}
