<?php

namespace App\Controller\Api;

use App\Entity\ServiceBooking;
use App\Repository\ServiceBookingRepository;
use App\Service\WebsocketEmitter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ServiceBookingController extends AbstractController
{
    #[Route('/api/service-bookings', name: 'api_service_booking_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        #[CurrentUser] ?\App\Entity\User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], 400);
            }

            $required = ['serviceId', 'serviceName', 'vehicleDescription', 'requestedDateTime', 'phone'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    return $this->json(['error' => "Field '$field' is required"], 400);
                }
            }

            $phone = trim((string) $data['phone']);
            if (strlen($phone) < 7 || strlen($phone) > 20) {
                return $this->json(['error' => 'Invalid phone number'], 400);
            }

            $pastError = $this->validateNotPastDateTime((string) $data['requestedDateTime']);
            if ($pastError) {
                return $pastError;
            }

            $serviceId = trim((string) $data['serviceId']);

            $existingBooking = $entityManager->getRepository(ServiceBooking::class)
                ->findOneBy([
                    'customer' => $user,
                    'serviceId' => $serviceId,
                    'status' => 'pending',
                ]);

            if ($existingBooking) {
                return $this->json(['error' => 'You already have a pending request for this service'], 409);
            }

            $booking = new ServiceBooking();
            $booking->setCustomer($user);
            $booking->setServiceId($serviceId);
            $booking->setServiceName(trim((string) $data['serviceName']));
            $booking->setVehicleDescription(trim((string) $data['vehicleDescription']));
            $booking->setRequestedDateTime(new \DateTime((string) $data['requestedDateTime']));
            $booking->setPhone($phone);
            $booking->setStatus('pending');
            if (!empty($data['notes'])) {
                $booking->setNotes(trim((string) $data['notes']));
            }

            $entityManager->persist($booking);
            $entityManager->flush();

            $logger->info('Service booking created', [
                'bookingId' => $booking->getId(),
                'customerId' => $user->getId(),
                'serviceId' => $serviceId,
            ]);

            $formatted = $this->formatBooking($booking);

            return $this->json([
                'success' => true,
                'message' => 'Service booking created successfully',
                'data' => ['id' => $booking->getId()],
                'booking' => $formatted,
            ], 201);
        } catch (\Exception $e) {
            $logger->error('Service booking error', ['error' => $e->getMessage()]);

            return $this->json(['error' => 'Booking failed: '.$e->getMessage()], 500);
        }
    }

    #[Route('/api/service-bookings', name: 'api_service_booking_list', methods: ['GET'])]
    public function list(
        ServiceBookingRepository $repository,
        Request $request,
        #[CurrentUser] ?\App\Entity\User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            if ($this->isStaff($user)) {
                $status = $request->query->get('status');
                $bookings = $status
                    ? $repository->findByStatus((string) $status)
                    : $repository->findAll();
            } else {
                $bookings = $repository->findByCustomer($user->getId());
            }

            $bookingsData = array_map(fn (ServiceBooking $b) => $this->formatBooking($b), $bookings);

            return $this->json([
                'success' => true,
                'count' => count($bookingsData),
                'data' => $bookingsData,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch bookings: '.$e->getMessage()], 500);
        }
    }

    #[Route('/api/service-bookings/{id}', name: 'api_service_booking_get', methods: ['GET'])]
    public function get(
        int $id,
        ServiceBookingRepository $repository,
        #[CurrentUser] ?\App\Entity\User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $booking = $repository->find($id);
            if (!$booking) {
                return $this->json(['error' => 'Booking not found'], 404);
            }

            if (!$this->isStaff($user) && $booking->getCustomer()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Access denied'], 403);
            }

            return $this->json([
                'success' => true,
                'data' => $this->formatBooking($booking),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch booking: '.$e->getMessage()], 500);
        }
    }

    #[Route('/api/service-bookings/{id}', name: 'api_service_booking_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager,
        ServiceBookingRepository $repository,
        LoggerInterface $logger,
        #[CurrentUser] ?\App\Entity\User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $booking = $repository->find($id);
            if (!$booking) {
                return $this->json(['error' => 'Booking not found'], 404);
            }

            if ($booking->getCustomer()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Access denied'], 403);
            }

            if ($booking->getStatus() !== 'pending') {
                return $this->json(['error' => 'Only pending bookings can be cancelled'], 400);
            }

            $entityManager->remove($booking);
            $entityManager->flush();

            $logger->info('Service booking deleted', ['bookingId' => $id]);

            return $this->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
            $logger->error('Service booking delete error', ['error' => $e->getMessage()]);

            return $this->json(['error' => 'Delete failed: '.$e->getMessage()], 500);
        }
    }

    #[Route('/api/service-bookings/{id}/approve', name: 'api_service_booking_approve', methods: ['PATCH'])]
    public function approve(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceBookingRepository $repository,
        WebsocketEmitter $websocketEmitter,
        LoggerInterface $logger,
        #[CurrentUser] ?\App\Entity\User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if (!$this->isStaff($user)) {
            return $this->json(['error' => 'Only staff can approve bookings'], 403);
        }

        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $booking = $repository->find($id);
            if (!$booking) {
                return $this->json(['error' => 'Booking not found'], 404);
            }

            $status = $data['status'] ?? null;
            if (!$status || !in_array($status, ['approved', 'rejected', 'completed'], true)) {
                return $this->json(['error' => 'Invalid status. Must be approved, rejected, or completed'], 400);
            }

            $booking->setStatus($status);
            $booking->setApprovedBy($user);
            $booking->setApprovedAt(new \DateTime());
            if (!empty($data['staffRemarks'])) {
                $booking->setStaffRemarks(trim((string) $data['staffRemarks']));
            }
            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $logger->info('Service booking '.$status, [
                'bookingId' => $booking->getId(),
                'approvedBy' => $user->getId(),
            ]);

            $customerId = $booking->getCustomer()?->getId();
            if ($customerId !== null) {
                $websocketEmitter->emitServiceUpdated($customerId, $this->formatBooking($booking));
            }

            return $this->json([
                'success' => true,
                'message' => 'Booking '.$status.' successfully',
                'data' => $this->formatBooking($booking),
            ]);
        } catch (\Exception $e) {
            $logger->error('Service booking approval error', ['error' => $e->getMessage()]);

            return $this->json(['error' => 'Approval failed: '.$e->getMessage()], 500);
        }
    }

    private function isStaff(\App\Entity\User $user): bool
    {
        return in_array('ROLE_STAFF', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    private function validateNotPastDateTime(string $dateTimeStr): ?JsonResponse
    {
        try {
            $requested = new \DateTime($dateTimeStr);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date/time format. Use YYYY-MM-DD HH:MM:SS'], 400);
        }

        if ($requested < new \DateTime()) {
            return $this->json(['error' => 'Cannot book a date or time in the past'], 400);
        }

        return null;
    }

    private function formatBooking(ServiceBooking $booking): array
    {
        $customer = $booking->getCustomer();

        return [
            'id' => $booking->getId(),
            'serviceId' => $booking->getServiceId(),
            'serviceName' => $booking->getServiceName(),
            'vehicleDescription' => $booking->getVehicleDescription(),
            'requestedDateTime' => $booking->getRequestedDateTime()?->format('Y-m-d H:i:s'),
            'phone' => $booking->getPhone(),
            'notes' => $booking->getNotes(),
            'status' => $booking->getStatus(),
            'staffRemarks' => $booking->getStaffRemarks(),
            'customer' => [
                'id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'fullName' => trim($customer->getFirstName().' '.$customer->getLastName()),
                'phone' => $customer->getPhone(),
            ],
            'approvedBy' => $booking->getApprovedBy() ? [
                'id' => $booking->getApprovedBy()->getId(),
                'email' => $booking->getApprovedBy()->getEmail(),
                'fullName' => trim($booking->getApprovedBy()->getFirstName().' '.$booking->getApprovedBy()->getLastName()),
            ] : null,
            'approvedAt' => $booking->getApprovedAt()?->format('Y-m-d H:i:s'),
            'createdAt' => $booking->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $booking->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
