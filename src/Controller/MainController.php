<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Add your data fetching logic here
        return $this->render('main/dashboard.html.twig', [
            'available_cars' => 24,
            'cars_sold' => 12,
            'pending_repairs' => 8,
            'awaiting_documents' => 5,
            'recent_activities' => [],
            'user' => [
                'name' => 'Elmar Lariosa',
                'avatar' => 'https://ui-avatars.com/api/?name=Tom+Cook'
            ]
        ]);
    }

    #[Route('/inventory', name: 'app_inventory')]
    public function inventory(): Response
    {
        return $this->render('main/inventory.html.twig');
    }

    #[Route('/sales', name: 'app_sales')]
    public function sales(): Response
    {
        return $this->render('main/sales.html.twig');
    }

    #[Route('/customers', name: 'app_customers')]
    public function customers(): Response
    {
        return $this->render('main/customers.html.twig');
    }

    #[Route('/repairs', name: 'app_repairs')]
    public function repairs(): Response
    {
        return $this->render('main/repairs.html.twig');
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

    #[Route('/users', name: 'app_users')]
    public function users(): Response
    {
        return $this->render('main/users.html.twig');
    }
}