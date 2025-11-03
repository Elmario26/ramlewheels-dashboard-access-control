<?php

namespace App\Repository;

use App\Entity\Cars;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cars>
 */
class CarsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cars::class);
    }

    /**
     * Get total inventory value
     */
    public function getTotalInventoryValue(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.price) as total')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Get count by condition
     */
    public function getCountByCondition(): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c.conditions, COUNT(c.id) as count')
            ->groupBy('c.conditions')
            ->getQuery()
            ->getResult();

        $conditions = [];
        foreach ($results as $result) {
            $conditions[$result['conditions']] = (int) $result['count'];
        }

        return $conditions;
    }

    /**
     * Get count by brand
     */
    public function getCountByBrand(int $limit = 5): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c.brand, COUNT(c.id) as count')
            ->groupBy('c.brand')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $brands = [];
        foreach ($results as $result) {
            $brands[$result['brand']] = (int) $result['count'];
        }

        return $brands;
    }

    /**
     * Get recent cars
     */
    public function getRecentCars(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get average vehicle price
     */
    public function getAveragePrice(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('AVG(c.price) as average')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Search cars by multiple criteria
     */
    public function searchCars(
        ?string $brand = null,
        ?string $condition = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?string $year = null,
        ?string $status = null,
        ?string $color = null,
        ?int $maxMileage = null
    ): array {
        $qb = $this->createQueryBuilder('c');

        if ($brand) {
            $qb->andWhere('c.brand = :brand')
               ->setParameter('brand', $brand);
        }

        if ($condition) {
            $qb->andWhere('c.conditions = :condition')
               ->setParameter('condition', $condition);
        }

        if ($minPrice !== null) {
            $qb->andWhere('c.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('c.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        if ($year) {
            $qb->andWhere('c.Year = :year')
               ->setParameter('year', $year);
        }

        if ($status) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        if ($color) {
            $qb->andWhere('c.color = :color')
               ->setParameter('color', $color);
        }

        if ($maxMileage !== null) {
            $qb->andWhere('c.Mileage <= :maxMileage')
               ->setParameter('maxMileage', $maxMileage);
        }

        return $qb->orderBy('c.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
