<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\DocumentActivityLog;
use App\Entity\Cars;
use App\Entity\Customer;
use App\Form\DocumentType;
use App\Repository\DocumentRepository;
use App\Repository\DocumentActivityLogRepository;
use App\Repository\CarsRepository;
use App\Repository\CustomerRepository;
use App\Service\ActivityLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use ZipArchive;

#[Route('/documents')]
final class DocumentsController extends AbstractController
{
    public function __construct(
        private ActivityLoggerService $activityLogger
    ) {}
    #[Route('/', name: 'app_documents_index', methods: ['GET'])]
    public function index(
        Request $request, 
        DocumentRepository $documentRepository,
        CarsRepository $carsRepository,
        CustomerRepository $customerRepository
    ): Response {
        $filters = [
            'q' => $request->query->get('q', ''),
            'category' => $request->query->get('category', ''),
            'type' => $request->query->get('type', ''),
            'vehicle_id' => $request->query->get('vehicle_id', ''),
            'customer_id' => $request->query->get('customer_id', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', ''),
        ];

        // Identify if any filters (excluding search) are applied
        $hasFilters = !empty(array_filter(
            $filters,
            fn($value, $key) => $key !== 'q' && $value !== '',
            ARRAY_FILTER_USE_BOTH
        ));

        // Filter documents
        if ($filters['q'] !== '') {
            // Text search by file name/type/description
            $documents = $documentRepository->searchDocuments($filters['q']);
        } elseif ($hasFilters) {
            // Other filters (category/type/vehicle/customer/date)
            $documents = $documentRepository->filterDocuments($filters);
        } else {
            // Default to recent documents
            $documents = $documentRepository->getRecentDocuments(100);
        }

        // Filter out Insurance Documents
        $documents = array_filter($documents, fn($doc) => $doc->getDocumentType() !== 'Insurance Documents');

        $documentsByType = $documentRepository->getDocumentsByType();
        $documentsByCategory = $documentRepository->getDocumentsByCategory();
        
        return $this->render('main/documents.html.twig', [
            'documents' => $documents,
            'documentsByType' => $documentsByType,
            'documentsByCategory' => $documentsByCategory,
            'vehicles' => $carsRepository->findAll(),
            'customers' => $customerRepository->findAll(),
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'app_documents_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        DocumentActivityLogRepository $activityLogRepository,
        LoggerInterface $logger
    ): Response {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var UploadedFile $file */
                $file = $form->get('file')->getData();
                
                // File is required for new documents
                if (!$document->getId() && !$file) {
                    $this->addFlash('error', 'Please select a file to upload.');
                    return $this->render('main/documents_form.html.twig', [
                        'document' => $document,
                        'form' => $form,
                    ]);
                }
                
                if ($file) {
                    // Generate auto-renamed filename
                    $newFilename = $this->generateAutoFilename($document, $file, $slugger, $entityManager);
                    // Capture metadata before moving (temp file is removed after move)
                    $fileSize = (string)$file->getSize();
                    $mimeType = $file->getMimeType();

                    try {
                        $file->move(
                            $this->getParameter('documents_directory'),
                            $newFilename
                        );
                        
                        $document->setFileName($file->getClientOriginalName());
                        $document->setFilePath($newFilename);
                        $document->setFileSize($fileSize);
                        $document->setMimeType($mimeType);
                        
                        // Set uploaded by (if user is logged in)
                        if ($this->getUser()) {
                            $document->setUploadedBy($this->getUser());
                        }
                        
                        // Handle related entity ID
                        $relatedEntityId = $form->get('relatedEntityId')->getData();
                        if ($relatedEntityId) {
                            $document->setRelatedEntityId((int)$relatedEntityId);
                        }
                        
                        $entityManager->persist($document);
                        $entityManager->flush();
                        
                        // Log activity - use our ActivityLoggerService
                        $this->activityLogger->logCreate(
                            'Document',
                            $document->getId(),
                            "Uploaded document: {$document->getFileName()}",
                            [
                                'fileName' => ['after' => $document->getFileName()],
                                'documentType' => ['after' => $document->getDocumentType()],
                                'fileSize' => ['after' => $fileSize],
                            ]
                        );
                        
                        // Also log activity locally (existing logging mechanism)
                        $this->logActivity($entityManager, $activityLogRepository, $document, 'uploaded', $request);
                        
                        $this->addFlash('success', 'Document uploaded successfully!');
                        return $this->redirectToRoute('app_documents_index');
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload document: ' . $e->getMessage());
                        $logger->error('Document upload failed during move', ['exception' => $e]);
                    }
                } else {
                    // No file uploaded but form is valid - shouldn't happen, but handle it
                    $this->addFlash('error', 'Please select a file to upload.');
                    $logger->error('Document upload failed: file missing after validation');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while uploading the document: ' . $e->getMessage());
                $logger->error('Document upload unexpected error', ['exception' => $e]);
            }
        }

        // If submitted but invalid, show form with errors (200 OK so browser/Turbo won't error)
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', implode(' | ', $errors));
            }
            return $this->render('main/documents_form.html.twig', [
                'document' => $document,
                'form' => $form,
            ]);
        }

        return $this->render('main/documents_form.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_documents_show', methods: ['GET'])]
    public function show(Document $document, DocumentActivityLogRepository $activityLogRepository): Response
    {
        $versions = [];
        if ($document->getParentDocument()) {
            $versions = $activityLogRepository->getEntityManager()
                ->getRepository(Document::class)
                ->getDocumentVersions($document->getParentDocument()->getId());
        } elseif ($document->isLatestVersion()) {
            $versions = $activityLogRepository->getEntityManager()
                ->getRepository(Document::class)
                ->getDocumentVersions($document->getId());
        }

        $activityLogs = $activityLogRepository->findByDocument($document->getId());

        return $this->render('main/documents_show.html.twig', [
            'document' => $document,
            'versions' => $versions,
            'activityLogs' => $activityLogs,
        ]);
    }

    #[Route('/{id}/preview', name: 'app_documents_preview', methods: ['GET'])]
    public function preview(Document $document): Response
    {
        return $this->render('main/documents_preview_modal.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/download', name: 'app_documents_download', methods: ['GET'])]
    public function download(
        Document $document, 
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentActivityLogRepository $activityLogRepository
    ): Response {
        $filePath = $this->getParameter('documents_directory') . '/' . $document->getFilePath();
        
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found.');
            return $this->redirectToRoute('app_documents_index');
        }

        // Log activity
        $this->logActivity($entityManager, $activityLogRepository, $document, 'downloaded', $request);

        $response = new Response(file_get_contents($filePath));
        $response->headers->set('Content-Type', $document->getMimeType() ?? 'application/octet-stream');
        
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getFileName()
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/{id}/view', name: 'app_documents_view', methods: ['GET'])]
    public function view(Document $document): Response
    {
        $filePath = $this->getParameter('documents_directory') . '/' . $document->getFilePath();
        
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found.');
            return $this->redirectToRoute('app_documents_index');
        }

        $response = new Response(file_get_contents($filePath));
        $response->headers->set('Content-Type', $document->getMimeType() ?? 'application/octet-stream');
        
        return $response;
    }

    #[Route('/{id}/new-version', name: 'app_documents_new_version', methods: ['GET', 'POST'])]
    public function newVersion(
        Request $request,
        Document $document,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        DocumentActivityLogRepository $activityLogRepository
    ): Response {
        // Mark current document as not latest
        $document->setIsLatestVersion(false);
        $entityManager->flush();

        // Create new version
        $newDocument = new Document();
        $newDocument->setCategory($document->getCategory());
        $newDocument->setDocumentType($document->getDocumentType());
        $newDocument->setParentDocument($document->getParentDocument() ?: $document);
        $newDocument->setRelatedEntityType($document->getRelatedEntityType());
        $newDocument->setRelatedEntityId($document->getRelatedEntityId());
        $newDocument->setDescription($document->getDescription());
        
        // Get next version number
        $versions = $entityManager->getRepository(Document::class)
            ->getDocumentVersions($document->getParentDocument() ? $document->getParentDocument()->getId() : $document->getId());
        $newDocument->setVersion(count($versions) + 1);

        $form = $this->createForm(DocumentType::class, $newDocument, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var UploadedFile $file */
                $file = $form->get('file')->getData();
                
                if (!$file) {
                    $this->addFlash('error', 'Please select a file to upload.');
                    return $this->render('main/documents_form.html.twig', [
                        'document' => $newDocument,
                        'form' => $form,
                        'isNewVersion' => true,
                        'parentDocument' => $document,
                    ]);
                }
                
                if ($file) {
                    $newFilename = $this->generateAutoFilename($newDocument, $file, $slugger, $entityManager);
                    $fileSize = (string)$file->getSize();
                    $mimeType = $file->getMimeType();

                    try {
                        $file->move(
                            $this->getParameter('documents_directory'),
                            $newFilename
                        );
                        
                        $newDocument->setFileName($file->getClientOriginalName());
                        $newDocument->setFilePath($newFilename);
                        $newDocument->setFileSize($fileSize);
                        $newDocument->setMimeType($mimeType);
                        $newDocument->setIsLatestVersion(true);
                        
                        if ($this->getUser()) {
                            $newDocument->setUploadedBy($this->getUser());
                        }
                        
                        $entityManager->persist($newDocument);
                        $entityManager->flush();
                        
                        // Log activity
                        $this->logActivity($entityManager, $activityLogRepository, $newDocument, 'version_created', $request);
                        
                        $this->addFlash('success', 'New version uploaded successfully!');
                        return $this->redirectToRoute('app_documents_show', ['id' => $newDocument->getId()]);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload new version: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while uploading the new version.');
            }
        }

        // If submitted but invalid, show form with errors (200 OK so browser/Turbo won't error)
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', implode(' | ', $errors));
            }
            return $this->render('main/documents_form.html.twig', [
                'document' => $newDocument,
                'form' => $form,
                'isNewVersion' => true,
                'parentDocument' => $document,
            ]);
        }

        return $this->render('main/documents_form.html.twig', [
            'document' => $newDocument,
            'form' => $form,
            'isNewVersion' => true,
            'parentDocument' => $document,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_documents_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Document $document, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        DocumentActivityLogRepository $activityLogRepository
    ): Response {
        $form = $this->createForm(DocumentType::class, $document, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var UploadedFile $file */
                $file = $form->get('file')->getData();
                
                if ($file) {
                    // Delete old file
                    $oldFilePath = $this->getParameter('documents_directory') . '/' . $document->getFilePath();
                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                    
                    // Upload new file
                    $newFilename = $this->generateAutoFilename($document, $file, $slugger, $entityManager);
                    $fileSize = (string)$file->getSize();
                    $mimeType = $file->getMimeType();

                    try {
                        $file->move(
                            $this->getParameter('documents_directory'),
                            $newFilename
                        );
                        
                        $document->setFileName($file->getClientOriginalName());
                        $document->setFilePath($newFilename);
                        $document->setFileSize($fileSize);
                        $document->setMimeType($mimeType);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload new file: ' . $e->getMessage());
                    }
                }
                
                if ($this->getUser()) {
                    $document->setUpdatedBy($this->getUser());
                }
                $document->setUpdatedAt(new \DateTime());
                
                // Handle related entity ID
                $relatedEntityId = $form->get('relatedEntityId')->getData();
                if ($relatedEntityId) {
                    $document->setRelatedEntityId((int)$relatedEntityId);
                }
                
                $entityManager->flush();
                
                // Log activity using ActivityLoggerService
                $this->activityLogger->logUpdate(
                    'Document',
                    $document->getId(),
                    "Updated document: {$document->getFileName()}",
                    [
                        'documentType' => ['after' => $document->getDocumentType()],
                        'category' => ['after' => $document->getCategory()],
                    ]
                );
                
                // Also log activity locally (existing logging mechanism)
                $this->logActivity($entityManager, $activityLogRepository, $document, 'updated', $request);
                
                $this->addFlash('success', 'Document updated successfully!');
                return $this->redirectToRoute('app_documents_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the document.');
            }
        }

        // If submitted but invalid, show form with errors (200 OK so browser/Turbo won't error)
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', implode(' | ', $errors));
            }
            return $this->render('main/documents_form.html.twig', [
                'document' => $document,
                'form' => $form,
            ]);
        }

        return $this->render('main/documents_form.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_documents_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Document $document, 
        EntityManagerInterface $entityManager,
        DocumentActivityLogRepository $activityLogRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            try {
                // Log activity using ActivityLoggerService before deletion
                $this->activityLogger->logDelete(
                    'Document',
                    $document->getId(),
                    "Deleted document: {$document->getFileName()}",
                    [
                        'fileName' => ['before' => $document->getFileName()],
                        'documentType' => ['before' => $document->getDocumentType()],
                    ]
                );
                
                // Also log activity locally (existing logging mechanism)
                $this->logActivity($entityManager, $activityLogRepository, $document, 'deleted', $request);
                
                // Delete physical file
                $filePath = $this->getParameter('documents_directory') . '/' . $document->getFilePath();
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                
                $entityManager->remove($document);
                $entityManager->flush();
                
                $this->addFlash('success', 'Document deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the document.');
            }
        } else {
            $this->addFlash('error', 'Invalid token.');
        }

        return $this->redirectToRoute('app_documents_index');
    }

    #[Route('/export/vehicle/{vehicleId}', name: 'app_documents_export_vehicle', methods: ['GET'])]
    public function exportVehicleDocuments(
        int $vehicleId,
        DocumentRepository $documentRepository,
        CarsRepository $carsRepository
    ): Response {
        $vehicle = $carsRepository->find($vehicleId);
        if (!$vehicle) {
            $this->addFlash('error', 'Vehicle not found.');
            return $this->redirectToRoute('app_documents_index');
        }

        $documents = $documentRepository->findByVehicle($vehicleId);
        
        if (empty($documents)) {
            $this->addFlash('warning', 'No documents found for this vehicle.');
            return $this->redirectToRoute('app_documents_index');
        }

        return $this->createZipArchive($documents, 'vehicle_' . $vehicleId . '_documents.zip');
    }

    #[Route('/export/customer/{customerId}', name: 'app_documents_export_customer', methods: ['GET'])]
    public function exportCustomerDocuments(
        int $customerId,
        DocumentRepository $documentRepository,
        CustomerRepository $customerRepository
    ): Response {
        $customer = $customerRepository->find($customerId);
        if (!$customer) {
            $this->addFlash('error', 'Customer not found.');
            return $this->redirectToRoute('app_documents_index');
        }

        $documents = $documentRepository->findByCustomer($customerId);
        
        if (empty($documents)) {
            $this->addFlash('warning', 'No documents found for this customer.');
            return $this->redirectToRoute('app_documents_index');
        }

        return $this->createZipArchive($documents, 'customer_' . $customerId . '_documents.zip');
    }

    private function generateAutoFilename(
        Document $document, 
        UploadedFile $file, 
        SluggerInterface $slugger,
        EntityManagerInterface $entityManager
    ): string {
        $date = date('Ymd');
        $extension = $file->guessExtension();
        
        // Get related entity name if available
        $entityName = '';
        if ($document->getRelatedEntityType() === 'Cars' && $document->getRelatedEntityId()) {
            $vehicle = $entityManager->getRepository(Cars::class)->find($document->getRelatedEntityId());
            if ($vehicle) {
                $entityName = $slugger->slug($vehicle->getBrand() . ' ' . $vehicle->getMake() . ' ' . $vehicle->getYear())->toString();
            }
        } elseif ($document->getRelatedEntityType() === 'Customer' && $document->getRelatedEntityId()) {
            $customer = $entityManager->getRepository(Customer::class)->find($document->getRelatedEntityId());
            if ($customer) {
                $entityName = $slugger->slug($customer->getFullName())->toString();
            }
        }
        
        $docType = $slugger->slug($document->getDocumentType())->toString();
        
        if ($entityName) {
            $filename = $entityName . '_' . $docType . '_' . $date . '.' . $extension;
        } else {
            $filename = $docType . '_' . $date . '_' . uniqid() . '.' . $extension;
        }
        
        return $filename;
    }

    private function createZipArchive(array $documents, string $zipName): Response
    {
        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'documents_');
        
        if ($zip->open($tempFile, ZipArchive::CREATE) !== TRUE) {
            $this->addFlash('error', 'Failed to create ZIP archive.');
            return $this->redirectToRoute('app_documents_index');
        }

        $documentsDirectory = $this->getParameter('documents_directory');
        
        foreach ($documents as $document) {
            $filePath = $documentsDirectory . '/' . $document->getFilePath();
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $document->getFileName());
            }
        }

        $zip->close();

        $response = new Response(file_get_contents($tempFile));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $zipName
        ));

        unlink($tempFile);

        return $response;
    }

    private function logActivity(
        EntityManagerInterface $entityManager,
        DocumentActivityLogRepository $activityLogRepository,
        Document $document,
        string $action,
        Request $request
    ): void {
        try {
            $log = new DocumentActivityLog();
            $log->setDocument($document);
            $log->setAction($action);
            $log->setIpAddress($request->getClientIp());
            
            if ($this->getUser()) {
                $log->setUser($this->getUser());
            }
            
            $entityManager->persist($log);
            $entityManager->flush();
        } catch (\Exception $e) {
            // Silently fail logging - don't break the main operation
        }
    }
}
