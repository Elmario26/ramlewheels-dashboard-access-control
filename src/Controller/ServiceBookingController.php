<?php

namespace App\Controller;

use App\Entity\ServiceBooking;
use App\Repository\ServiceBookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bookings/services')]
final class ServiceBookingController extends AbstractController
{
    #[Route('', name: 'app_service_bookings', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function index(ServiceBookingRepository $repository, Request $request): Response
    {
        $statusFilter = $request->query->get('status', 'pending');

        if ($statusFilter === 'all') {
            $bookings = $repository->findAll();
        } else {
            $bookings = $repository->findByStatus($statusFilter);
        }

        $totalBookings = count($repository->findAll());
        $pendingBookings = count($repository->findByStatus('pending'));
        $approvedBookings = count($repository->findByStatus('approved'));
        $rejectedBookings = count($repository->findByStatus('rejected'));
        $completedBookings = count($repository->findByStatus('completed'));

        return $this->render('admin/service_bookings.html.twig', [
            'bookings' => $bookings,
            'currentFilter' => $statusFilter,
            'totalBookings' => $totalBookings,
            'pendingBookings' => $pendingBookings,
            'approvedBookings' => $approvedBookings,
            'rejectedBookings' => $rejectedBookings,
            'completedBookings' => $completedBookings,
        ]);
    }

    #[Route('/{id}', name: 'app_service_booking_view', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function view(ServiceBooking $booking): Response
    {
        return $this->render('admin/service_booking_detail.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_service_booking_approve', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function approve(
        ServiceBooking $booking,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('approve' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('approved');
            $booking->setApprovedBy($this->getUser());
            $booking->setApprovedAt(new \DateTime());
            $booking->setStaffRemarks($request->request->get('staffRemarks'));
            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->addFlash('approved', sprintf('Service booking #%d has been approved.', $booking->getId()));
        }

        return $this->redirectToRoute('app_service_bookings', ['status' => 'pending']);
    }

    #[Route('/{id}/reject', name: 'app_service_booking_reject', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function reject(
        ServiceBooking $booking,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('reject' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('rejected');
            $booking->setApprovedBy($this->getUser());
            $booking->setApprovedAt(new \DateTime());
            $booking->setStaffRemarks($request->request->get('staffRemarks'));
            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->addFlash('rejected', sprintf('Service booking #%d was rejected.', $booking->getId()));
        }

        return $this->redirectToRoute('app_service_bookings', ['status' => 'pending']);
    }

    #[Route('/{id}/complete', name: 'app_service_booking_complete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function complete(
        ServiceBooking $booking,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('complete' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('completed');
            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->addFlash('completed', sprintf('Service booking #%d is marked as completed.', $booking->getId()));
        }

        return $this->redirectToRoute('app_service_bookings', ['status' => 'approved']);
    }
}
