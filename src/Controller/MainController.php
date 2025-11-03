<?php

namespace App\Controller;

use App\Entity\Cars;
use App\Form\CarsType;
use App\Repository\CarsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class MainController extends AbstractController
{

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
    public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $car = new Cars();
        $form = $this->createForm(CarsType::class, $car);
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
                $this->addFlash('success', 'Vehicle added successfully!');
                return $this->redirectToRoute('app_inventory', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while saving the vehicle.');
                return $this->redirectToRoute('app_inventory');
            }
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
    public function edit(Request $request, Cars $car, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
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
    public function delete(Request $request, Cars $car, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->request->get('_token'))) {
            try {
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
        return $this->render('main/documents.html.twig');
    }

    #[Route('/reports', name: 'app_reports')]
    public function reports(): Response
    {
        return $this->render('main/reports.html.twig');
    }

}