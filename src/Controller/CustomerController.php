<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Service\ActivityLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customers')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private ActivityLoggerService $activityLogger
    ) {}
    #[Route('/', name: 'app_customers_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        try {
            $customers = $customerRepository->getRecentCustomers(50);
            $statistics = $customerRepository->getCustomerStatistics();

            return $this->render('main/customers.html.twig', [
                'customers' => $customers,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            // Log the error and return a simple response for debugging
            error_log('Customer Controller Error: ' . $e->getMessage());
            
            return $this->render('main/customers.html.twig', [
                'customers' => [],
                'statistics' => [
                    'total_customers' => 0,
                    'new_this_month' => 0,
                    'customers_with_purchases' => 0,
                    'conversion_rate' => 0,
                ],
            ]);
        }
    }

    #[Route('/new', name: 'app_customers_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $customer->setUpdatedAt(new \DateTime());
                $entityManager->persist($customer);
                $entityManager->flush();

                // Log the activity
                $this->activityLogger->logCreate(
                    'Customer',
                    $customer->getId(),
                    "Created customer: {$customer->getFullName()}",
                    [
                        'firstName' => ['after' => $customer->getFirstName()],
                        'lastName' => ['after' => $customer->getLastName()],
                        'email' => ['after' => $customer->getEmail()],
                        'phone' => ['after' => $customer->getPhone()],
                    ]
                );

                $this->addFlash('success', 'Customer created successfully!');
                return $this->redirectToRoute('app_customers_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while creating the customer: ' . $e->getMessage());
            }
        }

        return $this->render('main/customer_form.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/search', name: 'app_customers_search', methods: ['GET'])]
    public function search(Request $request, CustomerRepository $customerRepository): Response
    {
        try {
            $query = trim($request->query->get('q', ''));
            $customers = [];
            $error = null;

            if (!empty($query)) {
                try {
                    $customers = $customerRepository->searchCustomers($query);
                    error_log('Customer search found ' . count($customers) . ' results for: ' . $query);
                } catch (\Exception $searchException) {
                    error_log('Customer search execution error: ' . $searchException->getMessage());
                    $error = 'Search failed: ' . $searchException->getMessage();
                    $customers = [];
                }
            } else {
                // If no search query, show recent customers
                $customers = $customerRepository->getRecentCustomers(50);
            }

            $statistics = $customerRepository->getCustomerStatistics();

            return $this->render('main/customers.html.twig', [
                'customers' => $customers,
                'searchQuery' => $query,
                'statistics' => $statistics,
                'error' => $error,
            ]);
        } catch (\Exception $e) {
            error_log('Customer Search Error: ' . $e->getMessage());
            
            return $this->render('main/customers.html.twig', [
                'customers' => [],
                'searchQuery' => $request->query->get('q', ''),
                'statistics' => [
                    'total_customers' => 0,
                    'new_this_month' => 0,
                    'customers_with_purchases' => 0,
                    'conversion_rate' => 0,
                ],
                'error' => 'Search failed: ' . $e->getMessage(),
            ]);
        }
    }

    #[Route('/{id}', name: 'app_customers_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('main/customer_details.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_customers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $customer->setUpdatedAt(new \DateTime());
                $entityManager->flush();

                // Log the activity
                $this->activityLogger->logUpdate(
                    'Customer',
                    $customer->getId(),
                    "Updated customer: {$customer->getFullName()}",
                    [
                        'firstName' => ['after' => $customer->getFirstName()],
                        'lastName' => ['after' => $customer->getLastName()],
                        'email' => ['after' => $customer->getEmail()],
                    ]
                );

                $this->addFlash('success', 'Customer updated successfully!');
                return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the customer: ' . $e->getMessage());
            }
        }

        return $this->render('main/customer_form.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customers_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->request->get('_token'))) {
            // Log the activity before deleting
            $this->activityLogger->logDelete(
                'Customer',
                $customer->getId(),
                "Deleted customer: {$customer->getFullName()}",
                [
                    'firstName' => ['before' => $customer->getFirstName()],
                    'lastName' => ['before' => $customer->getLastName()],
                    'email' => ['before' => $customer->getEmail()],
                ]
            );

            $entityManager->remove($customer);
            $entityManager->flush();
            $this->addFlash('success', 'Customer deleted successfully!');
        }

        return $this->redirectToRoute('app_customers_index');
    }

    #[Route('/{id}/details', name: 'app_customers_details', methods: ['GET'])]
    public function getDetails(Customer $customer): Response
    {
        return $this->json([
            'success' => true,
            'customer' => [
                'id' => $customer->getId(),
                'fullName' => $customer->getFullName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone(),
                'address' => $customer->getAddress(),
                'notes' => $customer->getNotes(),
            ]
        ]);
    }

}
