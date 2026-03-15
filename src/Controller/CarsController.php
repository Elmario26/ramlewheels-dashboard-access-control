<?php

namespace App\Controller;

use App\Entity\Cars;
use App\Form\CarsType;
use App\Repository\CarsRepository;
use App\Service\ActivityLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/cars')]
final class CarsController extends AbstractController
{
    public function __construct(
        private ActivityLoggerService $activityLogger
    ) {}
    #[Route('/{id}/view', name: 'app_cars_view', methods: ['GET'])]
    public function view(Cars $car): Response
    {
        return $this->render('main/car_view.html.twig', [
            'car' => $car,
        ]);
    }

    #[Route(name: 'app_cars_index', methods: ['GET'])]
    public function index(Request $request, CarsRepository $carsRepository): Response
    {
        // Get filter parameters
        $brand = $request->query->get('brand');
        $year = $request->query->get('year');
        $status = $request->query->get('status');
        $condition = $request->query->get('condition');
        $color = $request->query->get('color');
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $mileageMax = $request->query->get('mileage_max');

        // Build filters array
        $filters = [];
        if ($brand) $filters['brand'] = $brand;
        if ($year) $filters['year'] = $year;
        if ($status) $filters['status'] = $status;
        if ($condition) $filters['condition'] = $condition;
        if ($color) $filters['color'] = $color;
        if ($priceMin !== null && $priceMin !== '') $filters['price_min'] = (float)$priceMin;
        if ($priceMax !== null && $priceMax !== '') $filters['price_max'] = (float)$priceMax;
        if ($mileageMax) $filters['mileage_max'] = (int)$mileageMax;

        // Get filtered vehicles or all vehicles if no filters
        $vehicles = empty($filters) ? $carsRepository->findAll() : $carsRepository->searchCars(
            $filters['brand'] ?? null,
            $filters['condition'] ?? null,
            $filters['price_min'] ?? null,
            $filters['price_max'] ?? null,
            $filters['year'] ?? null,
            $filters['status'] ?? null,
            $filters['color'] ?? null,
            $filters['mileage_max'] ?? null
        );

        return $this->render('main/inventory.html.twig', [
            'vehicles' => $vehicles,
            'filterBrand' => $brand,
            'filterYear' => $year,
            'filterStatus' => $status,
            'filterCondition' => $condition,
            'filterColor' => $color,
            'filterPriceMin' => $priceMin,
            'filterPriceMax' => $priceMax,
            'filterMileageMax' => $mileageMax,
        ]);
    }

    #[Route('/new', name: 'app_cars_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $car = new Cars();
        $form = $this->createForm(CarsType::class, $car);
        $form->handleRequest($request);

        // For direct navigation, send users back to inventory and auto-open the modal.
        if ($request->isMethod('GET') && !$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('app_cars_index', ['openModal' => 'add']);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile[] $imageFiles */
            $imageFiles = $form->get('images')->getData();
            
            // Upload each image and collect their filenames
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
                        // Handle file upload error
                    }
                }
            }

            // Set the images on the car entity
            $car->setImages($fileNames);

            $entityManager->persist($car);
            $entityManager->flush();

            // Log the activity
            $this->activityLogger->logCreate(
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

            if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Vehicle added successfully!',
                    'redirect' => $this->generateUrl('app_cars_index'),
                ]);
            }

            return $this->redirectToRoute('app_cars_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Form validation failed.',
                'form' => $this->renderView('main/_form.html.twig', [
                    'car' => $car,
                    'form' => $form->createView(),
                ]),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Always return the partial for AJAX/modal loads.
        return $this->render('main/_form.html.twig', [
            'car' => $car,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_cars_show', methods: ['GET'])]
    public function show(Cars $car): Response
    {
        return $this->render('main/car_details.html.twig', [
            'car' => $car,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cars_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cars $car, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CarsType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile[] $imageFiles */
            $imageFiles = $form->get('images')->getData();
            
            // Upload new images if any were uploaded
            if (!empty($imageFiles)) {
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
                            // Handle file upload error
                        }
                    }
                }
                
                // Merge with existing images
                $existingImages = $car->getImages() ?? [];
                $car->setImages(array_merge($existingImages, $fileNames));
            }

            $entityManager->flush();

            // Log the activity
            $this->activityLogger->logUpdate(
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

            if ($request->headers->get('Accept') === 'application/json') {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Vehicle updated successfully!',
                ]);
            }

            return $this->redirectToRoute('app_cars_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && $request->headers->get('Accept') === 'application/json') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Form validation failed.',
            ]);
        }

        return $this->render('main/_form.html.twig', [
            'car' => $car,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit-form', name: 'app_cars_edit_form', methods: ['GET'])]
    public function editForm(Cars $car): Response
    {
        $form = $this->createForm(CarsType::class, $car);
        
        return $this->render('main/_form.html.twig', [
            'form' => $form->createView(),
            'car' => $car,
        ]);
    }

    #[Route('/{id}/data', name: 'app_cars_data', methods: ['GET'])]
    public function getCarData(Cars $car): JsonResponse
    {
        return new JsonResponse([
            'id' => $car->getId(),
            'brand' => $car->getBrand(),
            'year' => $car->getYear(),
            'mileage' => $car->getMileage(),
            'conditions' => $car->getConditions(),
            'price' => $car->getPrice(),
            'images' => $car->getImages(),
        ]);
    }

    #[Route('/{id}', name: 'app_cars_delete', methods: ['POST'])]
    public function delete(Request $request, Cars $car, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->getPayload()->getString('_token'))) {
            // Log the activity before deleting
            $this->activityLogger->logDelete(
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
        }

        return $this->redirectToRoute('app_cars_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete-image', name: 'app_cars_delete_image', methods: ['POST'])]
    public function deleteImage(Request $request, Cars $car, EntityManagerInterface $entityManager): JsonResponse
    {
        $imageName = $request->getPayload()->getString('image_name');
        
        if ($imageName && in_array($imageName, $car->getImages())) {
            // Remove image from entity
            $car->removeImage($imageName);
            $entityManager->flush();
            
            // Optionally delete the physical file
            $imagePath = $this->getParameter('car_images_directory') . '/' . $imageName;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Image not found'
        ], 400);
    }
}
