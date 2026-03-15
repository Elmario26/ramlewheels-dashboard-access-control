<?php

namespace App\Controller;

use App\Entity\Sales;
use App\Entity\Cars;
use App\Form\SalesType;
use App\Repository\SalesRepository;
use App\Repository\CarsRepository;
use App\Service\ActivityLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/sales')]
final class SalesController extends AbstractController
{
    public function __construct(
        private ActivityLoggerService $activityLogger
    ) {}
    #[Route('/', name: 'app_sales_index', methods: ['GET'])]
    public function index(SalesRepository $salesRepository): Response
    {
        try {
            $sales = $salesRepository->getRecentSales(50);
            $statistics = $salesRepository->getSalesStatistics();

            return $this->render('main/sales.html.twig', [
                'sales' => $sales,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            // Log the error and return a simple response for debugging
            error_log('Sales Controller Error: ' . $e->getMessage());
            
            return $this->render('main/sales.html.twig', [
                'sales' => [],
                'statistics' => [
                    'total_revenue' => 0,
                    'monthly_revenue' => 0,
                    'revenue_growth' => 0,
                    'total_sales' => 0,
                    'pending_sales' => 0,
                    'average_sale_price' => 0,
                    'conversion_rate' => 0,
                    'top_brand' => null,
                ],
            ]);
        }
    }

    #[Route('/new', name: 'app_sales_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CarsRepository $carsRepository): Response
    {
        $sale = new Sales();
        
        // Pre-select vehicle if provided in query parameter
        $vehicleId = $request->query->get('vehicle');
        if ($vehicleId) {
            $vehicle = $carsRepository->find($vehicleId);
            if ($vehicle && $vehicle->getStatus() === 'available') {
                $sale->setVehicle($vehicle);
            }
        }
        
        $form = $this->createForm(SalesType::class, $sale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Check if vehicle is already sold
                $vehicle = $sale->getVehicle();
                if ($vehicle && $vehicle->getStatus() === 'sold') {
                    $this->addFlash('error', 'This vehicle has already been sold.');
                    return $this->redirectToRoute('app_sales_new');
                }

                // Customer is now required and handled by the form
                // Set the user who created this sale
                $sale->setCreatedBy($this->getUser());

                // Set updated timestamp
                $sale->setUpdatedAt(new \DateTime());

                $entityManager->persist($sale);
                $entityManager->flush();

                // Log the activity
                $this->activityLogger->logCreate(
                    'Sale',
                    $sale->getId(),
                    "Created sale for {$sale->getVehicle()->getBrand()} {$sale->getVehicle()->getYear()}",
                    [
                        'vehicle' => ['after' => $sale->getVehicle()->getBrand()],
                        'customer' => ['after' => $sale->getCustomer()->getFullName()],
                        'price' => ['after' => $sale->getSalePrice()],
                        'status' => ['after' => $sale->getStatus()],
                    ]
                );

                // Update vehicle status to sold if sale is completed
                if ($sale->getStatus() === 'completed') {
                    $vehicle->setStatus('sold');
                    $entityManager->flush();
                }

                $this->addFlash('success', 'Sale recorded successfully!');
                return $this->redirectToRoute('app_sales_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while recording the sale.');
            }
        }

        return $this->render('main/sales_form.html.twig', [
            'sale' => $sale,
            'form' => $form,
        ]);
    }


    #[Route('/search', name: 'app_sales_search', methods: ['GET'])]
    public function search(Request $request, SalesRepository $salesRepository): Response
    {
        try {
            $query = trim($request->query->get('q', ''));
            $sales = [];
            $error = null;

            if (!empty($query)) {
                error_log('Search Query: ' . $query);
                error_log('Request Method: ' . $request->getMethod());
                error_log('Request URI: ' . $request->getUri());
                error_log('All Query Parameters: ' . json_encode($request->query->all()));
                
                try {
                    $sales = $salesRepository->searchSales($query);
                    error_log('Found ' . count($sales) . ' results');
                    
                    // Debug output for each sale found
                    foreach ($sales as $sale) {
                        error_log(sprintf(
                            'Sale ID: %d, Customer: %s %s, Vehicle: %s %s %s, Price: %s',
                            $sale->getId(),
                            $sale->getCustomer() ? $sale->getCustomer()->getFirstName() : 'N/A',
                            $sale->getCustomer() ? $sale->getCustomer()->getLastName() : 'N/A',
                            $sale->getVehicle() ? $sale->getVehicle()->getBrand() : 'N/A',
                            $sale->getVehicle() ? $sale->getVehicle()->getMake() : 'N/A',
                            $sale->getVehicle() ? $sale->getVehicle()->getYear() : 'N/A',
                            $sale->getSalePrice()
                        ));
                    }
                } catch (\Exception $searchException) {
                    error_log('Search execution error: ' . $searchException->getMessage());
                    $error = 'Search failed: ' . $searchException->getMessage();
                    $sales = [];
                }
            } else {
                // If no search query, show recent sales
                $sales = $salesRepository->getRecentSales(50);
            }

            // Get statistics
            $statistics = $salesRepository->getSalesStatistics();
            
            error_log('Request headers: ' . json_encode($request->headers->all()));
            
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                error_log('Returning JSON response for search query: ' . $query);
                $response = new JsonResponse(array_map(function($sale) {
                    return [
                        'id' => $sale->getId(),
                        'customer' => $sale->getCustomer() ? $sale->getCustomer()->getFirstName() . ' ' . $sale->getCustomer()->getLastName() : 'N/A',
                        'vehicle' => $sale->getVehicle() ? $sale->getVehicle()->getBrand() . ' ' . $sale->getVehicle()->getMake() . ' ' . $sale->getVehicle()->getYear() : 'N/A',
                        'saleDate' => $sale->getSaleDate()->format('Y-m-d'),
                        'salePrice' => $sale->getSalePrice(),
                        'status' => $sale->getStatus(),
                        'paymentMethod' => $sale->getPaymentMethod() ?? 'N/A'
                    ];
                }, $sales));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            // Regular HTML response
            return $this->render('main/sales.html.twig', [
                'sales' => $sales,
                'searchQuery' => $query,
                'statistics' => $statistics,
                'error' => $error,
            ]);
        } catch (\Exception $e) {
            error_log('Sales Search Error: ' . $e->getMessage());
            
            return $this->render('main/sales.html.twig', [
                'sales' => [],
                'searchQuery' => $request->query->get('q', ''),
                'statistics' => [
                    'total_revenue' => 0,
                    'monthly_revenue' => 0,
                    'revenue_growth' => 0,
                    'total_sales' => 0,
                    'pending_sales' => 0,
                    'average_sale_price' => 0,
                    'conversion_rate' => 0,
                    'top_brand' => null,
                ],
                'error' => 'Search failed: ' . $e->getMessage(),
            ]);
        }
    }

    #[Route('/filter', name: 'app_sales_filter', methods: ['GET'])]
    public function filter(Request $request, SalesRepository $salesRepository): Response
    {
        try {
            $status = $request->query->get('status');
            $dateFrom = $request->query->get('date_from');
            $dateTo = $request->query->get('date_to');
            $paymentMethod = $request->query->get('payment_method');
            
            $sales = $salesRepository->getFilteredSales([
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'payment_method' => $paymentMethod
            ]);

            $statistics = $salesRepository->getSalesStatistics();

            return $this->render('main/sales.html.twig', [
                'sales' => $sales,
                'filterStatus' => $status,
                'filterDateFrom' => $dateFrom,
                'filterDateTo' => $dateTo,
                'filterPaymentMethod' => $paymentMethod,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            error_log('Sales Filter Error: ' . $e->getMessage());
            
            return $this->render('main/sales.html.twig', [
                'sales' => [],
                'statistics' => [
                    'total_revenue' => 0,
                    'monthly_revenue' => 0,
                    'revenue_growth' => 0,
                    'total_sales' => 0,
                    'pending_sales' => 0,
                    'average_sale_price' => 0,
                    'conversion_rate' => 0,
                    'top_brand' => null,
                ],
            ]);
        }
    }






    #[Route('/export/csv', name: 'app_sales_export_csv', methods: ['GET'])]
    public function exportCsv(SalesRepository $salesRepository): Response
    {
        try {
            $sales = $salesRepository->findAll();
            
            $csvData = "Sale ID,Customer,Vehicle,Brand,Model,Year,Sale Date,Sale Price,Status,Payment Method,Notes\n";
            
            foreach ($sales as $sale) {
                $customerName = $sale->getCustomer() ? 
                    $sale->getCustomer()->getFirstName() . ' ' . $sale->getCustomer()->getLastName() : 
                    'N/A';
                
                $vehicleInfo = $sale->getVehicle();
                $vehicleBrand = $vehicleInfo ? $vehicleInfo->getBrand() : 'N/A';
                $vehicleModel = $vehicleInfo ? $vehicleInfo->getMake() : 'N/A';
                $vehicleYear = $vehicleInfo ? $vehicleInfo->getYear() : 'N/A';
                
                $csvData .= sprintf(
                    "%d,\"%s\",\"%s\",\"%s\",\"%s\",%d,\"%s\",%.2f,\"%s\",\"%s\",\"%s\"\n",
                    $sale->getId(),
                    $customerName,
                    $vehicleBrand . ' ' . $vehicleModel,
                    $vehicleBrand,
                    $vehicleModel,
                    $vehicleYear,
                    $sale->getSaleDate()->format('Y-m-d'),
                    $sale->getSalePrice(),
                    $sale->getStatus(),
                    $sale->getPaymentMethod() ?? 'N/A',
                    $sale->getNotes() ?? ''
                );
            }
            
            $response = new Response($csvData);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'sales_export_' . date('Y-m-d_H-i-s') . '.csv'
            ));
            
            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while exporting sales data.');
            return $this->redirectToRoute('app_sales_index');
        }
    }

    #[Route('/export/pdf', name: 'app_sales_export_pdf', methods: ['GET'])]
    public function exportPdf(SalesRepository $salesRepository): Response
    {
        try {
            error_log('Starting PDF export');
            error_log('PHP version: ' . PHP_VERSION);
            error_log('Memory limit: ' . ini_get('memory_limit'));
            error_log('Max execution time: ' . ini_get('max_execution_time'));
            // Get sales data and statistics
            $sales = $salesRepository->findAll();
            $statistics = $salesRepository->getSalesStatistics();
            
            if (empty($sales)) {
                $this->addFlash('warning', 'No sales data available to export.');
                return $this->redirectToRoute('app_sales_index');
            }
            
            // Configure DomPDF options
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->setIsRemoteEnabled(true);
            $options->setIsPhpEnabled(true);
            $options->setIsHtml5ParserEnabled(true);
            $options->setIsFontSubsettingEnabled(true);
            
            // Set up font directories
            $projectDir = $this->getParameter('kernel.project_dir');
            $fontDir = $projectDir . '/var/fonts';
            if (!is_dir($fontDir)) {
                mkdir($fontDir, 0777, true);
            }
            $options->setFontDir($fontDir);
            $options->setFontCache($fontDir);
            
            // Create Dompdf instance
            $dompdf = new Dompdf($options);
            
            // Set base path for loading assets (e.g. images)
            $dompdf->setBasePath($projectDir . '/public');
            
            // Render HTML template
            $html = $this->renderView('main/sales_export_pdf_test.html.twig', [
                'sales' => $sales,
                'exportDate' => new \DateTime()
            ]);
            
            // Load HTML and set paper
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            
            // Render PDF
            $dompdf->render();
            
            // Get PDF content
            $pdfContent = $dompdf->output();
            
            // Prepare response
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'sales_report_' . date('Y-m-d_His') . '.pdf'
            );
            $response->headers->set('Content-Disposition', $disposition);
            
            return $response;
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while exporting sales data to PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_sales_index');
        }
    }

    #[Route('/{id}', name: 'app_sales_show', methods: ['GET'])]
    public function show(Sales $sale): Response
    {
        return $this->render('main/sales_details.html.twig', [
            'sale' => $sale,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sales_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sales $sale, EntityManagerInterface $entityManager): Response
    {
        // Check authorization: staff can only edit their own sales, admins can edit any
        if (!$this->isGranted('ROLE_ADMIN')) {
            // If not admin, check if staff owns this sale
            $currentUser = $this->getUser();
            $createdBy = $sale->getCreatedBy();
            
            // Staff can only edit if they created this sale
            if (!$createdBy || $createdBy->getId() !== $currentUser->getId()) {
                throw new AccessDeniedException('You do not have permission to edit this sale.');
            }
        }
        
        $originalStatus = $sale->getStatus();
        $originalVehicle = $sale->getVehicle();
        
        $form = $this->createForm(SalesType::class, $sale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $sale->setUpdatedAt(new \DateTime());
                $entityManager->flush();

                // Log the activity
                $this->activityLogger->logUpdate(
                    'Sale',
                    $sale->getId(),
                    "Updated sale status to {$sale->getStatus()}",
                    [
                        'status' => ['before' => $originalStatus, 'after' => $sale->getStatus()],
                        'price' => ['after' => $sale->getSalePrice()],
                    ]
                );

                // Handle vehicle status changes
                $newStatus = $sale->getStatus();
                $vehicle = $sale->getVehicle();

                // If status changed from completed to something else, or vehicle changed
                if (($originalStatus === 'completed' && $newStatus !== 'completed') || 
                    ($originalVehicle && $originalVehicle->getId() !== $vehicle->getId())) {
                    // Reset original vehicle status if it was sold
                    if ($originalVehicle && $originalVehicle->getStatus() === 'sold') {
                        $originalVehicle->setStatus('available');
                        $entityManager->flush();
                    }
                }

                // If new status is completed, mark vehicle as sold
                if ($newStatus === 'completed' && $vehicle) {
                    $vehicle->setStatus('sold');
                    $entityManager->flush();
                }

                $this->addFlash('success', 'Sale updated successfully!');
                return $this->redirectToRoute('app_sales_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the sale.');
            }
        }

        return $this->render('main/sales_form.html.twig', [
            'sale' => $sale,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sales_delete', methods: ['POST'])]
    public function delete(Request $request, Sales $sale, EntityManagerInterface $entityManager): Response
    {
        // Check authorization: staff can only delete their own sales, admins can delete any
        if (!$this->isGranted('ROLE_ADMIN')) {
            // If not admin, check if staff owns this sale
            $currentUser = $this->getUser();
            $createdBy = $sale->getCreatedBy();
            
            // Staff can only delete if they created this sale
            if (!$createdBy || $createdBy->getId() !== $currentUser->getId()) {
                throw new AccessDeniedException('You do not have permission to delete this sale.');
            }
        }
        
        if ($this->isCsrfTokenValid('delete'.$sale->getId(), $request->request->get('_token'))) {
            try {
                // Log the activity before deleting
                $this->activityLogger->logDelete(
                    'Sale',
                    $sale->getId(),
                    "Deleted sale for {$sale->getVehicle()->getBrand()} {$sale->getVehicle()->getYear()}",
                    [
                        'vehicle' => ['before' => $sale->getVehicle()->getBrand()],
                        'price' => ['before' => $sale->getSalePrice()],
                    ]
                );

                // Reset vehicle status if it was sold
                $vehicle = $sale->getVehicle();
                if ($vehicle && $vehicle->getStatus() === 'sold') {
                    $vehicle->setStatus('available');
                    $entityManager->flush();
                }

                $entityManager->remove($sale);
                $entityManager->flush();
                $this->addFlash('success', 'Sale deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the sale.');
            }
        } else {
            $this->addFlash('error', 'Invalid token.');
        }

        return $this->redirectToRoute('app_sales_index');
    }

    #[Route('/{id}/form', name: 'app_sales_edit_form', methods: ['GET'])]
    public function editForm(Sales $sale): Response
    {
        $form = $this->createForm(SalesType::class, $sale);
        
        return $this->render('main/sales_form.html.twig', [
            'form' => $form,
            'sale' => $sale,
        ]);
    }
}
