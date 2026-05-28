<?php

namespace App\Controller;

use App\Entity\ServiceBooking;
use App\Repository\ServiceBookingRepository;
use App\Service\WebsocketEmitter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $context = $this->buildIndexContext($repository, (string) $request->query->get('status', 'pending'));

        return $this->render('admin/service_bookings.html.twig', $context);
    }

    #[Route('/live-data', name: 'app_service_bookings_live', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function liveData(
        ServiceBookingRepository $repository,
        Request $request
    ): JsonResponse {
        $context = $this->buildIndexContext($repository, (string) $request->query->get('status', 'pending'));
        /** @var list<ServiceBooking> $bookings */
        $bookings = $context['bookings'];

        return $this->json([
            'signature' => $this->buildSignature($bookings),
            'hasRows' => \count($bookings) > 0,
            'bookingIds' => array_map(static fn (ServiceBooking $b) => $b->getId(), $bookings),
            'stats' => [
                'total' => $context['totalBookings'],
                'pending' => $context['pendingBookings'],
                'approved' => $context['approvedBookings'],
                'rejected' => $context['rejectedBookings'],
                'completed' => $context['completedBookings'],
            ],
            'filterCounts' => [
                'pending' => $context['pendingBookings'],
                'approved' => $context['approvedBookings'],
                'rejected' => $context['rejectedBookings'],
                'completed' => $context['completedBookings'],
                'all' => $context['totalBookings'],
            ],
            'rowsHtml' => $this->renderView('admin/partials/_service_bookings_rows.html.twig', [
                'bookings' => $bookings,
            ]),
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
        WebsocketEmitter $websocketEmitter,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('approve' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('approved');
            $booking->setApprovedBy($this->getUser());
            $booking->setApprovedAt(new \DateTime());
            $booking->setStaffRemarks($request->request->get('staffRemarks'));
            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->emitStaffBookingUpdate($websocketEmitter, $booking);
            $this->addFlash('approved', sprintf('Service booking #%d has been approved.', $booking->getId()));
        }

        return $this->redirectToRoute('app_service_bookings', ['status' => 'pending']);
    }

    #[Route('/{id}/reject', name: 'app_service_booking_reject', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function reject(
        ServiceBooking $booking,
        EntityManagerInterface $entityManager,
        WebsocketEmitter $websocketEmitter,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('reject' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('rejected');
            $booking->setApprovedBy($this->getUser());
            $booking->setApprovedAt(new \DateTime());
            $booking->setStaffRemarks($request->request->get('staffRemarks'));
            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->emitStaffBookingUpdate($websocketEmitter, $booking);
            $this->addFlash('rejected', sprintf('Service booking #%d was rejected.', $booking->getId()));
        }

        return $this->redirectToRoute('app_service_bookings', ['status' => 'pending']);
    }

    #[Route('/{id}/complete', name: 'app_service_booking_complete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function complete(
        ServiceBooking $booking,
        EntityManagerInterface $entityManager,
        WebsocketEmitter $websocketEmitter,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('complete' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('completed');
            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->emitStaffBookingUpdate($websocketEmitter, $booking);
            $this->addFlash('completed', sprintf('Service booking #%d is marked as completed.', $booking->getId()));
        }

        return $this->redirectToRoute('app_service_bookings', ['status' => 'approved']);
    }

    /**
     * @return array{
     *     bookings: list<ServiceBooking>,
     *     currentFilter: string,
     *     totalBookings: int,
     *     pendingBookings: int,
     *     approvedBookings: int,
     *     rejectedBookings: int,
     *     completedBookings: int
     * }
     */
    private function buildIndexContext(ServiceBookingRepository $repository, string $statusFilter): array
    {
        if ($statusFilter === 'all') {
            $bookings = $repository->findAll();
        } else {
            $bookings = $repository->findByStatus($statusFilter);
        }

        $totalBookings = \count($repository->findAll());
        $pendingBookings = \count($repository->findByStatus('pending'));
        $approvedBookings = \count($repository->findByStatus('approved'));
        $rejectedBookings = \count($repository->findByStatus('rejected'));
        $completedBookings = \count($repository->findByStatus('completed'));

        return [
            'bookings' => $bookings,
            'currentFilter' => $statusFilter,
            'totalBookings' => $totalBookings,
            'pendingBookings' => $pendingBookings,
            'approvedBookings' => $approvedBookings,
            'rejectedBookings' => $rejectedBookings,
            'completedBookings' => $completedBookings,
        ];
    }

    /**
     * @param list<ServiceBooking> $bookings
     */
    private function buildSignature(array $bookings): string
    {
        $ids = array_map(static fn (ServiceBooking $booking) => (int) $booking->getId(), $bookings);
        rsort($ids);
        $ids = \array_slice($ids, 0, 15);

        return \count($bookings).':'.implode(',', $ids);
    }

    private function emitStaffBookingUpdate(WebsocketEmitter $websocketEmitter, ServiceBooking $booking): void
    {
        $websocketEmitter->emitServiceUpdatedForStaff([
            'id' => $booking->getId(),
            'status' => $booking->getStatus(),
            'serviceName' => $booking->getServiceName(),
            'updatedAt' => $booking->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}
