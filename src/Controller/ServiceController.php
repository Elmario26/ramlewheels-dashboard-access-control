<?php

namespace App\Controller;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use App\Repository\CustomerRepository;
use App\Repository\CarsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/services')]
class ServiceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServiceRepository $serviceRepository,
        private CustomerRepository $customerRepository,
        private CarsRepository $carsRepository,
        private UserRepository $userRepository
    ) {}

    #[Route('/', name: 'app_services_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $searchQuery = $request->query->get('q');
        $statusFilter = $request->query->get('status');
        
        if ($searchQuery) {
            $services = $this->serviceRepository->searchServices($searchQuery);
        } elseif ($statusFilter) {
            $services = $this->serviceRepository->findByStatus($statusFilter);
        } else {
            $services = $this->serviceRepository->findAll();
        }

        $statistics = $this->serviceRepository->getServiceStatistics();

        return $this->render('main/services.html.twig', [
            'services' => $services,
            'statistics' => $statistics,
            'searchQuery' => $searchQuery,
            'statusFilter' => $statusFilter
        ]);
    }

    #[Route('/search', name: 'app_services_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $searchQuery = $request->query->get('q');
        
        if (!$searchQuery) {
            return $this->redirectToRoute('app_services_index');
        }

        $services = $this->serviceRepository->searchServices($searchQuery);
        $statistics = $this->serviceRepository->getServiceStatistics();

        return $this->render('main/services.html.twig', [
            'services' => $services,
            'statistics' => $statistics,
            'searchQuery' => $searchQuery
        ]);
    }

    #[Route('/new', name: 'app_services_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($service);
            $this->entityManager->flush();

            $this->addFlash('success', 'Service created successfully!');
            return $this->redirectToRoute('app_services_show', ['id' => $service->getId()]);
        }

        return $this->render('main/service_form.html.twig', [
            'service' => $service,
            'form' => $form,
            'title' => 'Add New Service'
        ]);
    }

    #[Route('/{id}', name: 'app_services_show', methods: ['GET'])]
    public function show(Service $service): Response
    {
        return $this->render('main/service_details.html.twig', [
            'service' => $service
        ]);
    }

    #[Route('/{id}/edit', name: 'app_services_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service): Response
    {
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $this->addFlash('success', 'Service updated successfully!');
            return $this->redirectToRoute('app_services_show', ['id' => $service->getId()]);
        }

        return $this->render('main/service_form.html.twig', [
            'service' => $service,
            'form' => $form,
            'title' => 'Edit Service'
        ]);
    }

    #[Route('/{id}', name: 'app_services_delete', methods: ['POST'])]
    public function delete(Request $request, Service $service): Response
    {
        if ($this->isCsrfTokenValid('delete' . $service->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($service);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Service deleted successfully!');
        }

        return $this->redirectToRoute('app_services_index');
    }

    #[Route('/{id}/complete', name: 'app_services_complete', methods: ['POST'])]
    public function complete(Request $request, Service $service): Response
    {
        if ($this->isCsrfTokenValid('complete' . $service->getId(), $request->request->get('_token'))) {
            $service->setStatus('completed');
            $service->setCompletionDate(new \DateTime());
            $service->setUpdatedAt(new \DateTime());
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Service marked as completed!');
        }

        return $this->redirectToRoute('app_services_show', ['id' => $service->getId()]);
    }

    #[Route('/{id}/start', name: 'app_services_start', methods: ['POST'])]
    public function start(Request $request, Service $service): Response
    {
        if ($this->isCsrfTokenValid('start' . $service->getId(), $request->request->get('_token'))) {
            $service->setStatus('in_progress');
            $service->setUpdatedAt(new \DateTime());
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Service started!');
        }

        return $this->redirectToRoute('app_services_show', ['id' => $service->getId()]);
    }

    #[Route('/api/statistics', name: 'app_services_api_statistics', methods: ['GET'])]
    public function apiStatistics(): JsonResponse
    {
        $statistics = $this->serviceRepository->getServiceStatistics();
        return $this->json($statistics);
    }

    #[Route('/api/recent', name: 'app_services_api_recent', methods: ['GET'])]
    public function apiRecent(): JsonResponse
    {
        $recentServices = $this->serviceRepository->findRecentServices(5);
        
        $data = array_map(function(Service $service) {
            return [
                'id' => $service->getId(),
                'serviceType' => $service->getServiceType(),
                'customer' => $service->getCustomer()->getFullName(),
                'cost' => $service->getCost(),
                'status' => $service->getStatus(),
                'serviceDate' => $service->getServiceDate()->format('Y-m-d H:i:s')
            ];
        }, $recentServices);

        return $this->json($data);
    }

    #[Route('/{id}/assign-mechanic', name: 'app_services_assign_mechanic', methods: ['POST'])]
    public function assignMechanic(Request $request, Service $service): Response
    {
        $mechanicId = $request->request->get('mechanic_id');
        
        if ($this->isCsrfTokenValid('assign' . $service->getId(), $request->request->get('_token'))) {
            if ($mechanicId) {
                $mechanic = $this->userRepository->find($mechanicId);
                if ($mechanic && $mechanic->getRole() === 'mechanic') {
                    $service->setAssignedMechanic($mechanic);
                    $service->setUpdatedAt(new \DateTime());
                    $this->entityManager->flush();
                    
                    $this->addFlash('success', 'Mechanic assigned successfully!');
                } else {
                    $this->addFlash('error', 'Invalid mechanic selected!');
                }
            } else {
                $service->setAssignedMechanic(null);
                $service->setUpdatedAt(new \DateTime());
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Mechanic assignment removed!');
            }
        }

        return $this->redirectToRoute('app_services_show', ['id' => $service->getId()]);
    }

    #[Route('/api/mechanics', name: 'app_services_api_mechanics', methods: ['GET'])]
    public function apiMechanics(): JsonResponse
    {
        $mechanics = $this->userRepository->findMechanics();
        
        $data = array_map(function($mechanic) {
            return [
                'id' => $mechanic->getId(),
                'name' => $mechanic->getFullName(),
                'email' => $mechanic->getEmail(),
                'phone' => $mechanic->getPhone()
            ];
        }, $mechanics);

        return $this->json($data);
    }
}
