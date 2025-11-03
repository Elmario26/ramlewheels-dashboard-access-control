<?php

namespace App\Controller;

use App\Repository\CarsRepository;
use App\Repository\SalesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(CarsRepository $carsRepository, SalesRepository $salesRepository): Response
    {
        try {
            // Get statistics efficiently using repository methods
            $totalCars = $carsRepository->count([]);
            $carsAvailable = $carsRepository->count(['status' => 'available']);
            
            // Get real sales data
            $salesStats = $salesRepository->getSalesStatistics();
            $carsSold = $salesStats['total_sales'];
            $pendingRepairs = $salesStats['pending_sales'];
            $documentsAwaiting = 0; // Placeholder for future documents feature
        } catch (\Exception $e) {
            // Log the error and use fallback values
            error_log('Dashboard Controller Error: ' . $e->getMessage());
            
            $totalCars = 0;
            $carsAvailable = 0;
            $carsSold = 0;
            $pendingRepairs = 0;
            $documentsAwaiting = 0;
            $salesStats = [
                'total_revenue' => 0,
                'monthly_revenue' => 0,
                'revenue_growth' => 0,
                'total_sales' => 0,
                'pending_sales' => 0,
            ];
        }
        
        try {
            // Calculate price statistics using optimized queries
            $totalValue = $carsRepository->getTotalInventoryValue();
            $averageValue = $carsRepository->getAveragePrice();
            
            // Get condition and brand distributions
            $conditions = $carsRepository->getCountByCondition();
            // Ensure all conditions are present with 0 if not in DB
            $conditions = array_merge(
                ['Excellent' => 0, 'Good' => 0, 'Fair' => 0, 'Poor' => 0],
                $conditions
            );
            
            $topBrands = $carsRepository->getCountByBrand(5);
            $recentCars = $carsRepository->getRecentCars(5);
            
            // Prepare chart data
            $monthlyData = $this->generateMonthlyData($totalCars);
            
            // Get recent sales for dashboard
            $recentSales = $salesRepository->getRecentSales(5);
        } catch (\Exception $e) {
            error_log('Dashboard Controller Error (part 2): ' . $e->getMessage());
            
            $totalValue = 0;
            $averageValue = 0;
            $conditions = ['Excellent' => 0, 'Good' => 0, 'Fair' => 0, 'Poor' => 0];
            $topBrands = [];
            $recentCars = [];
            $monthlyData = $this->generateMonthlyData(0);
            $recentSales = [];
        }
        
        return $this->render('main/dashboard.html.twig', [
            'totalCars' => $totalCars,
            'carsAvailable' => $carsAvailable,
            'carsSold' => $carsSold,
            'pendingRepairs' => $pendingRepairs,
            'documentsAwaiting' => $documentsAwaiting,
            'totalValue' => $totalValue,
            'averageValue' => $averageValue,
            'conditions' => $conditions,
            'topBrands' => $topBrands,
            'monthlyData' => $monthlyData,
            'recentCars' => $recentCars,
            'recentSales' => $recentSales,
            'salesStats' => $salesStats,
        ]);
    }
    
    private function generateMonthlyData(int $totalCars): array
    {
        // Generate sample monthly data based on total cars
        $baseValue = max(1, (int)($totalCars / 6));
        return [
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June'],
            'sales' => [
                $baseValue + rand(0, 3),
                $baseValue + rand(0, 5),
                $baseValue + rand(0, 4),
                $baseValue + rand(0, 6),
                $baseValue + rand(0, 3),
                $baseValue + rand(0, 4),
            ],
            'inventory' => [
                $totalCars + rand(-5, 5),
                $totalCars + rand(-8, 3),
                $totalCars + rand(-6, 4),
                $totalCars + rand(-7, 6),
                $totalCars + rand(-4, 8),
                $totalCars,
            ],
        ];
    }
}
