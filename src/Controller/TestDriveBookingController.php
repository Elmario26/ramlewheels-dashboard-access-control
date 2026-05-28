<?php

namespace App\Controller;

use App\Repository\TestDriveBookingRepository;
use App\Entity\TestDriveBooking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/test-drive-bookings')]
final class TestDriveBookingController extends AbstractController
{
    #[Route('', name: 'app_test_drive_bookings', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function index(
        TestDriveBookingRepository $repository,
        Request $request
    ): Response {
        // Get status filter from query parameter
        $statusFilter = $request->query->get('status', 'pending');
        
        // Get bookings based on filter
        if ($statusFilter === 'all') {
            $bookings = $repository->findAll();
        } else {
            $bookings = $repository->findByStatus($statusFilter);
        }
        
        // Get statistics
        $totalBookings = count($repository->findAll());
        $pendingBookings = count($repository->findByStatus('pending'));
        $approvedBookings = count($repository->findByStatus('approved'));
        $rejectedBookings = count($repository->findByStatus('rejected'));
        
        return $this->render('admin/test_drive_bookings.html.twig', [
            'bookings' => $bookings,
            'currentFilter' => $statusFilter,
            'totalBookings' => $totalBookings,
            'pendingBookings' => $pendingBookings,
            'approvedBookings' => $approvedBookings,
            'rejectedBookings' => $rejectedBookings,
        ]);
    }

    #[Route('/{id}', name: 'app_test_drive_booking_view', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function view(TestDriveBooking $booking): Response
    {
        return $this->render('admin/test_drive_booking_detail.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_test_drive_booking_approve', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function approve(
        TestDriveBooking $booking,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('approve' . $booking->getId(), $request->request->get('_token'))) {
            $remarks = $request->request->get('staffRemarks');
            
            $booking->setStatus('approved');
            $booking->setApprovedBy($this->getUser());
            $booking->setApprovedAt(new \DateTime());
            $booking->setStaffRemarks($remarks);
            $booking->setUpdatedAt(new \DateTime());
            
            $entityManager->flush();
            
            $this->addFlash('approved', sprintf('Test drive booking #%d has been approved.', $booking->getId()));
        }
        
        return $this->redirectToRoute('app_test_drive_bookings', ['status' => 'pending']);
    }

    #[Route('/{id}/reject', name: 'app_test_drive_booking_reject', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function reject(
        TestDriveBooking $booking,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('reject' . $booking->getId(), $request->request->get('_token'))) {
            $remarks = $request->request->get('staffRemarks');
            
            $booking->setStatus('rejected');
            $booking->setApprovedBy($this->getUser());
            $booking->setApprovedAt(new \DateTime());
            $booking->setStaffRemarks($remarks);
            $booking->setUpdatedAt(new \DateTime());
            
            $entityManager->flush();
            
            $this->addFlash('rejected', sprintf('Test drive booking #%d was rejected.', $booking->getId()));
        }
        
        return $this->redirectToRoute('app_test_drive_bookings', ['status' => 'pending']);
    }

    #[Route('/{id}/complete', name: 'app_test_drive_booking_complete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function complete(
        TestDriveBooking $booking,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('complete' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('completed');
            $booking->setUpdatedAt(new \DateTime());
            
            $entityManager->flush();
            
            $this->addFlash('completed', sprintf('Test drive booking #%d is marked as completed.', $booking->getId()));
        }
        
        return $this->redirectToRoute('app_test_drive_bookings', ['status' => 'approved']);
    }
}
