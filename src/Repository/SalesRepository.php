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
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT status, COUNT(id) as count FROM sales GROUP BY status';
        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        $statusCounts = [];
        foreach ($result as $row) {
            $status = trim($row['status'] ?? '');
            $statusCounts[$status] = (int)($row['count'] ?? 0);
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
            ->orderBy('s.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sales by date range
     */
    public function getSalesByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.customer', 'c')
            ->leftJoin('s.vehicle', 'v')
            ->addSelect('c', 'v')
            ->where('s.saleDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('s.saleDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

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

        // Search in customer fields - using LIKE for case-insensitive comparison
        $orX->add($qb->expr()->like('c.firstName', ':query'))
            ->add($qb->expr()->like('c.lastName', ':query'))
            ->add($qb->expr()->like('c.email', ':query'))
            ->add($qb->expr()->like('c.phone', ':query'))
            ->add($qb->expr()->like('CONCAT(c.firstName, \' \', c.lastName)', ':query'));

        // Search in vehicle fields - using LIKE for case-insensitive comparison
        $orX->add($qb->expr()->like('v.brand', ':query'))
            ->add($qb->expr()->like('v.make', ':query'))
            ->add($qb->expr()->like('v.color', ':query'))
            ->add($qb->expr()->like('CAST(v.year AS string)', ':query'));

        // Search in sale fields - using LIKE for case-insensitive comparison
        $orX->add($qb->expr()->like('CAST(s.id AS string)', ':query'))
            ->add($qb->expr()->like('CAST(s.salePrice AS string)', ':query'))
            ->add($qb->expr()->like('s.paymentMethod', ':query'));

        $qb->where($orX)
           ->setParameter('query', '%' . strtolower($query) . '%');

        // Store the original search term for logging
        $searchTerm = $query;
        $query = $qb->getQuery();
        
        // Debug the actual SQL being executed
        error_log('Search Query Debug ----------------');
        error_log('Search Term: ' . $searchTerm); // Log the original search term
        error_log('Generated SQL: ' . $query->getSQL());
        error_log('Parameters: ' . json_encode($query->getParameters()->toArray()));
        
        try {
            // Execute query and get results
            $results = $query->getResult();
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
        error_log('Search query SQL: ' . $query->getSQL());
        error_log('Search query parameters: ' . json_encode($query->getParameters()->toArray()));
        
        $result = $query->getResult();
        error_log('Number of results: ' . count($result));
        error_log('Result details: ' . json_encode(array_map(function($sale) {
            return [
                'id' => $sale->getId(),
                'customer' => $sale->getCustomer() ? $sale->getCustomer()->getFirstName() . ' ' . $sale->getCustomer()->getLastName() : 'N/A'
            ];
        }, $result)));
        
        return $result;
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


    /**
     * Get top selling brands for a date range
     */
    public function getTopSellingBrands(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo, int $limit = 5): array
    {
        // Get all sales for the date range and process in PHP
        $sales = $this->createQueryBuilder('s')
            ->select('s', 'v')
            ->leftJoin('s.vehicle', 'v')
            ->where('s.saleDate >= :dateFrom')
            ->andWhere('s.saleDate <= :dateTo')
            ->andWhere('s.status = :status')
            ->setParameter('dateFrom', $dateFrom)
            ->setParameter('dateTo', $dateTo)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getResult();

        $brandData = [];
        foreach ($sales as $sale) {
            if (!$sale->getVehicle()) {
                continue;
            }
            $brand = $sale->getVehicle()->getBrand() ?? 'Unknown';
            if (!isset($brandData[$brand])) {
                $brandData[$brand] = [
                    'name' => $brand,
                    'sales_count' => 0,
                    'total_revenue' => 0.0
                ];
            }
            $brandData[$brand]['sales_count']++;
            $brandData[$brand]['total_revenue'] += (float)($sale->getSalePrice() ?? 0);
        }

        // Sort by sales count descending
        uasort($brandData, function ($a, $b) {
            return $b['sales_count'] <=> $a['sales_count'];
        });

        // Return top N brands
        return array_slice(array_values($brandData), 0, $limit);
    }
}
