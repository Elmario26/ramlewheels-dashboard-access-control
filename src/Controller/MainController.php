<?php

namespace App\Controller;

use App\Entity\Cars;
use App\Form\CarsType;
use App\Repository\CarsRepository;
use App\Repository\DocumentRepository;
use App\Repository\SalesRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('landing/home.html.twig');
    }

    #[Route('/about-us', name: 'app_marketing_about', methods: ['GET'])]
    public function marketingAbout(): Response
    {
        return $this->render('landing/about.html.twig');
    }


    #[Route('/contact', name: 'app_marketing_contact', methods: ['GET'])]
    public function marketingContact(): Response
    {
        return $this->render('landing/contact.html.twig');
    }

    #[Route('/inventory', name: 'app_inventory', methods: ['GET'])]
    public function inventory(Request $request, CarsRepository $carsRepository): Response
    {
        $form = $this->createForm(CarsType::class, new Cars(), [
            'action' => $this->generateUrl('app_inventory_create')
        ]);

        return $this->render('main/inventory.html.twig', [
            'vehicles' => $carsRepository->findAll(),
            'filters' => [
                'category' => 'all',
                'status' => 'all',
                'price_min' => null,
                'price_max' => null
            ],
            'form' => $form->createView()
        ]);
    }

    #[Route('/inventory', name: 'app_inventory_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, \App\Service\ActivityLoggerService $activityLogger): Response
    {
        $car = new Cars();
        $form = $this->createForm(CarsType::class, $car, [
            'csrf_protection' => !($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json'),
            'allow_extra_fields' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var UploadedFile[] $imageFiles */
                $imageFiles = $form->get('images')->getData();
                
                if ($imageFiles && count($imageFiles) > 0) {
                    foreach ($imageFiles as $imageFile) {
                        if ($imageFile instanceof UploadedFile) {
                            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                            try {
                                $imageFile->move(
                                    $this->getParameter('car_images_directory'),
                                    $newFilename
                                );
                                $car->addImage($newFilename);
                            } catch (FileException $e) {
                                $this->addFlash('error', 'Failed to upload one or more images: ' . $e->getMessage());
                            }
                        }
                    }
                }

                $entityManager->persist($car);
                $entityManager->flush();

                // Log the activity
                $activityLogger->logCreate(
                    'Vehicle',
                    $car->getId(),
                    "Created vehicle: {$car->getBrand()} {$car->getYear()}",
                    [
                        'brand' => ['after' => $car->getBrand()],
                        'year' => ['after' => $car->getYear()],
                        'price' => ['after' => $car->getPrice()],
                        'mileage' => ['after' => $car->getMileage()],
                    ]
                );

                // Return JSON for AJAX requests
                if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Vehicle added successfully!',
                        'redirect' => $this->generateUrl('app_inventory'),
                    ]);
                }

                $this->addFlash('success', 'Vehicle added successfully!');
                return $this->redirectToRoute('app_inventory', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Return JSON for AJAX requests
                if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'An error occurred while saving the vehicle: ' . $e->getMessage(),
                    ], Response::HTTP_BAD_REQUEST);
                }

                $this->addFlash('error', 'An error occurred while saving the vehicle.');
                return $this->redirectToRoute('app_inventory');
            }
        }

        // Handle validation errors for AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = [
                    'field' => $error->getOrigin()?->getName() ?? 'general',
                    'message' => $error->getMessage(),
                ];
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Form validation failed.',
                'errors' => $errors,
                'form' => $this->renderView('main/_form.html.twig', [
                    'car' => $car,
                    'form' => $form->createView(),
                ]),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->render('main/_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/inventory/new', name: 'app_inventory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $car = new Cars();
        $form = $this->createForm(CarsType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($car);
            $entityManager->flush();
            $this->addFlash('success', 'Vehicle added successfully!');
            return $this->redirectToRoute('app_inventory');
        }

        return $this->render('main/_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/inventory/{id}', name: 'app_inventory_view', methods: ['GET'])]
    public function view(Cars $car): Response
    {
        return $this->render('main/car_details.html.twig', [
            'car' => $car
        ]);
    }

    #[Route('/inventory/{id}/edit', name: 'app_inventory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cars $car, EntityManagerInterface $entityManager, SluggerInterface $slugger, \App\Service\ActivityLoggerService $activityLogger): Response
    {
        $form = $this->createForm(CarsType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle image deletions
                $imagesToDelete = $request->request->all('images_to_delete');
                if (!empty($imagesToDelete)) {
                    $currentImages = $car->getImages() ?? [];
                    
                    foreach ($imagesToDelete as $imageToDelete) {
                        // Remove from entity
                        $car->removeImage($imageToDelete);
                        
                        // Delete physical file
                        $imagePath = $this->getParameter('car_images_directory') . '/' . $imageToDelete;
                        if (file_exists($imagePath)) {
                            @unlink($imagePath);
                        }
                    }
                }
                
                /** @var UploadedFile[] $imageFiles */
                $imageFiles = $form->get('images')->getData();
                
                // Upload new images if any were uploaded
                if ($imageFiles && count($imageFiles) > 0) {
                    $fileNames = [];
                    foreach ($imageFiles as $imageFile) {
                        if ($imageFile instanceof UploadedFile) {
                            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                            try {
                                $imageFile->move(
                                    $this->getParameter('car_images_directory'),
                                    $newFilename
                                );
                                $fileNames[] = $newFilename;
                            } catch (FileException $e) {
                                $this->addFlash('error', 'Failed to upload one or more images: ' . $e->getMessage());
                            }
                        }
                    }
                    
                    // Merge with existing images (after deletions)
                    if (count($fileNames) > 0) {
                        $existingImages = $car->getImages() ?? [];
                        $car->setImages(array_merge($existingImages, $fileNames));
                    }
                }

                $entityManager->flush();

                // Log the activity
                $activityLogger->logUpdate(
                    'Vehicle',
                    $car->getId(),
                    "Updated vehicle: {$car->getBrand()} {$car->getYear()}",
                    [
                        'brand' => ['after' => $car->getBrand()],
                        'year' => ['after' => $car->getYear()],
                        'price' => ['after' => $car->getPrice()],
                        'mileage' => ['after' => $car->getMileage()],
                    ]
                );

                $this->addFlash('success', 'Vehicle updated successfully!');
                
                if ($request->headers->get('Accept') === 'application/json') {
                    return new \Symfony\Component\HttpFoundation\JsonResponse([
                        'success' => true,
                        'message' => 'Vehicle updated successfully!',
                    ]);
                }
                
                return $this->redirectToRoute('app_inventory');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the vehicle.');
                
                if ($request->headers->get('Accept') === 'application/json') {
                    return new \Symfony\Component\HttpFoundation\JsonResponse([
                        'success' => false,
                        'message' => 'An error occurred while updating the vehicle.',
                    ]);
                }
            }
        }

        if ($form->isSubmitted() && $request->headers->get('Accept') === 'application/json') {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => false,
                'message' => 'Form validation failed.',
            ]);
        }

        return $this->render('main/_form.html.twig', [
            'form' => $form->createView(),
            'car' => $car
        ]);
    }

    #[Route('/inventory/{id}', name: 'app_inventory_delete', methods: ['POST'])]
    public function delete(Request $request, Cars $car, EntityManagerInterface $entityManager, \App\Service\ActivityLoggerService $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->request->get('_token'))) {
            try {
                // Log before deletion
                $activityLogger->logDelete(
                    'Vehicle',
                    $car->getId(),
                    "Deleted vehicle: {$car->getBrand()} {$car->getYear()}",
                    [
                        'brand' => ['before' => $car->getBrand()],
                        'year' => ['before' => $car->getYear()],
                        'price' => ['before' => $car->getPrice()],
                    ]
                );

                $entityManager->remove($car);
                $entityManager->flush();
                $this->addFlash('success', 'Vehicle deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the vehicle.');
            }
        } else {
            $this->addFlash('error', 'Invalid token.');
        }

        return $this->redirectToRoute('app_inventory');
    }


    #[Route('/customers', name: 'app_customers')]
    public function customers(): Response
    {
        return $this->redirectToRoute('app_customers_index');
    }

    #[Route('/repairs', name: 'app_repairs')]
    public function repairs(): Response
    {
        return $this->redirectToRoute('app_services_index');
    }

    #[Route('/documents', name: 'app_documents')]
    public function documents(): Response
    {
        return $this->redirectToRoute('app_documents_index');
    }

    #[Route('/reports', name: 'app_reports')]
    public function reports(
        SalesRepository $salesRepository,
        CarsRepository $carsRepository,
        ServiceRepository $serviceRepository,
        DocumentRepository $documentRepository
    ): Response {
        $now = new \DateTimeImmutable('now');
        
        // Get sales status counts
        $salesStatusCounts = ['completed' => 0, 'pending' => 0, 'cancelled' => 0];
        
        // Get sales stats
        $salesStats = [
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'revenue_growth' => 0,
            'total_sales' => 0,
            'pending_sales' => 0,
            'average_sale_price' => 0,
            'conversion_rate' => 0,
            'top_brand' => null,
        ];
        
        $recentSales = [];
        $topSellingVehicles = [];

        try {
            // Get sales status counts using QueryBuilder
            $statusResults = $salesRepository->createQueryBuilder('s')
                ->select('s.status, COUNT(s.id) as count')
                ->groupBy('s.status')
                ->getQuery()
                ->getArrayResult();
            
            foreach ($statusResults as $row) {
                $status = $row['status'] ?? '';
                if (isset($salesStatusCounts[$status])) {
                    $salesStatusCounts[$status] = (int)$row['count'];
                }
            }
            
            // Get total sales revenue
            $totalRevenue = $salesRepository->createQueryBuilder('s')
                ->select('SUM(s.salePrice) as total')
                ->where('s.status = :status')
                ->setParameter('status', 'completed')
                ->getQuery()
                ->getSingleScalarResult();
            
            // Get monthly revenue (December)
            $monthStart = new \DateTime('2025-12-01 00:00:00');
            $monthEnd = new \DateTime('2025-12-31 23:59:59');
            $monthlyRevenue = $salesRepository->createQueryBuilder('s')
                ->select('SUM(s.salePrice) as total')
                ->where('s.status = :status')
                ->andWhere('s.saleDate >= :start')
                ->andWhere('s.saleDate <= :end')
                ->setParameter('status', 'completed')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();
            
            // Get average sale price
            $avgPrice = $salesRepository->createQueryBuilder('s')
                ->select('AVG(s.salePrice) as avg')
                ->where('s.status = :status')
                ->setParameter('status', 'completed')
                ->getQuery()
                ->getSingleScalarResult();
            
            $totalSales = $salesStatusCounts['completed'];
            
            $salesStats = [
                'total_revenue' => (float)($totalRevenue ?? 0),
                'monthly_revenue' => (float)($monthlyRevenue ?? 0),
                'revenue_growth' => 0,
                'total_sales' => $totalSales,
                'pending_sales' => $salesStatusCounts['pending'],
                'average_sale_price' => (float)($avgPrice ?? 0),
                'conversion_rate' => $totalSales > 0 ? 100 : 0,
                'top_brand' => 'Test1',
            ];
            
            $recentSales = $salesRepository->getRecentSales(6);
            $topSellingVehicles = $salesRepository->getTopSellingVehicles(5);
        } catch (\Throwable $e) {
            // On error, keep the defaults initialized above
        }

        try {
            $monthlySales = $salesRepository->getMonthlySalesData((int) $now->format('Y'));
            $monthlyLabels = [];
            $monthlyCounts = [];
            $monthlyRevenue = [];

            for ($i = 1; $i <= 12; $i++) {
                $monthName = (new \DateTime("2020-$i-01"))->format('M');
                $monthlyLabels[] = $monthName;
                $monthlyCounts[] = (int) ($monthlySales[$i]['sales_count'] ?? 0);
                $monthlyRevenue[] = round((float) ($monthlySales[$i]['revenue'] ?? 0), 2);
            }

            $brandWindowStart = $now->modify('-6 months');
            $topBrandsRaw = $salesRepository->getTopSellingBrands($brandWindowStart, $now, 5);
            $topBrands = [
                'labels' => array_map(static fn ($row) => $row['name'] ?? 'N/A', $topBrandsRaw),
                'sales' => array_map(static fn ($row) => (int) ($row['sales_count'] ?? 0), $topBrandsRaw),
                'revenue' => array_map(static fn ($row) => (float) ($row['total_revenue'] ?? 0), $topBrandsRaw),
            ];
        } catch (\Throwable $e) {
            $monthlyLabels = [];
            $monthlyCounts = [];
            $monthlyRevenue = [];
            $topBrands = ['labels' => [], 'sales' => [], 'revenue' => []];
        }

        try {
            $conditions = $carsRepository->getCountByCondition();
            $conditions = array_merge(
                ['Excellent' => 0, 'Good' => 0, 'Fair' => 0, 'Poor' => 0],
                $conditions
            );

            $inventoryTotals = [
                'total' => $carsRepository->count([]),
                'available' => $carsRepository->count(['status' => 'available']),
                'value' => $carsRepository->getTotalInventoryValue(),
                'average' => $carsRepository->getAveragePrice(),
                'brandMix' => $carsRepository->getCountByBrand(5),
            ];
            $inventoryTotals['sold'] = max(0, (int) ($salesStats['total_sales'] ?? 0));
        } catch (\Throwable $e) {
            error_log('Reports (inventory) error: ' . $e->getMessage());
            $conditions = ['Excellent' => 0, 'Good' => 0, 'Fair' => 0, 'Poor' => 0];
            $inventoryTotals = [
                'total' => 0,
                'available' => 0,
                'value' => 0,
                'average' => 0,
                'brandMix' => [],
                'sold' => 0,
            ];
        }

        try {
            $serviceStats = $serviceRepository->getServiceStatistics();
        } catch (\Throwable $e) {
            error_log('Reports (service) error: ' . $e->getMessage());
            $serviceStats = [
                'total_services' => 0,
                'completed_services' => 0,
                'pending_services' => 0,
                'in_progress_services' => 0,
                'total_revenue' => 0,
                'this_month_services' => 0,
                'completion_rate' => 0,
            ];
        }

        try {
            $documentsByCategory = $documentRepository->getDocumentsByCategory();
            $documentCategoryLabels = array_map(
                static fn ($row) => $row['category'] ?? 'Uncategorized',
                $documentsByCategory
            );
            $documentCategoryCounts = array_map(
                static fn ($row) => (int) ($row['count'] ?? 0),
                $documentsByCategory
            );
            $documentsTotal = $documentRepository->count([]);
        } catch (\Throwable $e) {
            error_log('Reports (documents) error: ' . $e->getMessage());
            $documentCategoryLabels = [];
            $documentCategoryCounts = [];
            $documentsTotal = 0;
        }

        $chartData = [
            'monthlyLabels' => $monthlyLabels,
            'monthlySales' => $monthlyCounts,
            'monthlyRevenue' => $monthlyRevenue,
            'salesPipeline' => [
                'labels' => ['completed', 'pending', 'cancelled'],
                'values' => [
                    $salesStatusCounts['completed'] ?? 0,
                    $salesStatusCounts['pending'] ?? 0,
                    $salesStatusCounts['cancelled'] ?? 0,
                ],
            ],
            'topBrands' => $topBrands,
            'inventory' => [
                'labels' => array_keys($conditions),
                'values' => array_values($conditions),
                'available' => $inventoryTotals['available'],
                'sold' => $inventoryTotals['sold'],
                'total' => $inventoryTotals['total'],
            ],
            'service' => [
                'completed' => $serviceStats['completed_services'] ?? 0,
                'pending' => $serviceStats['pending_services'] ?? 0,
                'inProgress' => $serviceStats['in_progress_services'] ?? 0,
            ],
            'documents' => [
                'labels' => $documentCategoryLabels,
                'values' => $documentCategoryCounts,
            ],
        ];

        error_log('chartData: ' . json_encode($chartData));

        return $this->render('main/reports.html.twig', [
            'salesStats' => $salesStats,
            'inventoryTotals' => $inventoryTotals,
            'serviceStats' => $serviceStats,
            'documentsTotal' => $documentsTotal,
            'recentSales' => $recentSales,
            'topSellingVehicles' => $topSellingVehicles,
            'chartData' => $chartData,
        ]);
    }

}