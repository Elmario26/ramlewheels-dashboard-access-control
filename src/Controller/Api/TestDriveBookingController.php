<?php

namespace App\Controller\Api;

use App\Entity\TestDriveBooking;
use App\Entity\Cars;
use App\Repository\TestDriveBookingRepository;
use App\Repository\CarsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Psr\Log\LoggerInterface;

final class TestDriveBookingController extends AbstractController
{
    #[Route('/api/test-drive-bookings', name: 'api_testdrive_create', methods: ['POST'])]
    public function createBooking(
        Request $request,
        EntityManagerInterface $entityManager,
        CarsRepository $carsRepository,
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

            // Validate required fields
            $required = ['carId', 'requestedDateTime'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->json(['error' => "Field '$field' is required"], 400);
                }
            }

            // Get car
            $car = $carsRepository->find($data['carId']);
            if (!$car) {
                return $this->json(['error' => 'Car not found'], 404);
            }

            // Check for duplicate pending bookings
            $existingBooking = $entityManager->getRepository(TestDriveBooking::class)
                ->findOneBy([
                    'customer' => $user,
                    'car' => $car,
                    'status' => 'pending'
                ]);

            if ($existingBooking) {
                return $this->json(['error' => 'You already have a pending booking for this car'], 409);
            }

            $pastError = $this->validateNotPastDateTime($data['requestedDateTime']);
            if ($pastError) {
                return $pastError;
            }

            // Create booking
            $booking = new TestDriveBooking();
            $booking->setCustomer($user);
            $booking->setCar($car);
            $booking->setRequestedDateTime(new \DateTime($data['requestedDateTime']));
            $booking->setStatus('pending');
            if (!empty($data['notes'])) {
                $booking->setNotes($data['notes']);
            }

            $entityManager->persist($booking);
            $entityManager->flush();

            $logger->info('Test drive booking created', [
                'bookingId' => $booking->getId(),
                'customerId' => $user->getId(),
                'carId' => $car->getId(),
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Test drive booking created successfully',
                'booking' => $this->formatBooking($booking),
            ], 201);

        } catch (\Exception $e) {
            $logger->error('Test drive booking error', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Booking failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/test-drive-bookings', name: 'api_testdrive_list', methods: ['GET'])]
    public function listBookings(
        TestDriveBookingRepository $repository,
        Request $request,
        #[CurrentUser] ?\App\Entity\User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            // Staff sees all bookings, customers see only their own
            if (in_array('ROLE_STAFF', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
                $status = $request->query->get('status');
                if ($status) {
                    $bookings = $repository->findByStatus($status);
                } else {
                    $bookings = $repository->findAll();
                }
            } else {
                $bookings = $repository->findByCustomer($user->getId());
            }

            $bookingsData = array_map(fn(TestDriveBooking $b) => $this->formatBooking($b), $bookings);

            return $this->json([
                'success' => true,
                'count' => count($bookingsData),
                'data' => $bookingsData,
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch bookings: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/test-drive-bookings/{id}', name: 'api_testdrive_get', methods: ['GET'])]
    public function getBooking(
        int $id,
        TestDriveBookingRepository $repository,
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

            // Check permission - customer can only see their own bookings
            if (!in_array('ROLE_STAFF', $user->getRoles()) && 
                !in_array('ROLE_ADMIN', $user->getRoles()) &&
                $booking->getCustomer()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Access denied'], 403);
            }

            return $this->json([
                'success' => true,
                'data' => $this->formatBooking($booking),
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch booking: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/test-drive-bookings/{id}', name: 'api_testdrive_update', methods: ['PUT', 'PATCH'])]
    public function updateBooking(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        TestDriveBookingRepository $repository,
        CarsRepository $carsRepository,
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
                return $this->json(['error' => 'Only pending bookings can be edited'], 400);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], 400);
            }

            if (!empty($data['carId'])) {
                $car = $carsRepository->find($data['carId']);
                if (!$car) {
                    return $this->json(['error' => 'Car not found'], 404);
                }
                $booking->setCar($car);
            }

            if (!empty($data['requestedDateTime'])) {
                $pastError = $this->validateNotPastDateTime($data['requestedDateTime']);
                if ($pastError) {
                    return $pastError;
                }
                $booking->setRequestedDateTime(new \DateTime($data['requestedDateTime']));
            }

            if (array_key_exists('notes', $data)) {
                $booking->setNotes($data['notes'] ?: null);
            }

            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $logger->info('Test drive booking updated', ['bookingId' => $booking->getId()]);

            return $this->json([
                'success' => true,
                'message' => 'Booking updated successfully',
                'data' => $this->formatBooking($booking),
            ]);
        } catch (\Exception $e) {
            $logger->error('Booking update error', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/test-drive-bookings/{id}', name: 'api_testdrive_delete', methods: ['DELETE'])]
    public function deleteBooking(
        int $id,
        EntityManagerInterface $entityManager,
        TestDriveBookingRepository $repository,
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

            $logger->info('Test drive booking deleted', ['bookingId' => $id]);

            return $this->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
            $logger->error('Booking delete error', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/test-drive-bookings/{id}/approve', name: 'api_testdrive_approve', methods: ['PATCH'])]
    public function approveBooking(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        TestDriveBookingRepository $repository,
        LoggerInterface $logger,
        #[CurrentUser] ?\App\Entity\User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Only staff can approve
        if (!in_array('ROLE_STAFF', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['error' => 'Only staff can approve bookings'], 403);
        }

        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $booking = $repository->find($id);

            if (!$booking) {
                return $this->json(['error' => 'Booking not found'], 404);
            }

            $status = $data['status'] ?? null;
            if (!$status || !in_array($status, ['approved', 'rejected'])) {
                return $this->json(['error' => 'Invalid status. Must be "approved" or "rejected"'], 400);
            }

            $booking->setStatus($status);
            $booking->setApprovedBy($user);
            $booking->setApprovedAt(new \DateTime());
            
            if (!empty($data['staffRemarks'])) {
                $booking->setStaffRemarks($data['staffRemarks']);
            }

            $booking->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $logger->info('Test drive booking ' . $status, [
                'bookingId' => $booking->getId(),
                'approvedBy' => $user->getId(),
                'status' => $status,
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Booking ' . $status . ' successfully',
                'data' => $this->formatBooking($booking),
            ]);

        } catch (\Exception $e) {
            $logger->error('Booking approval error', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Approval failed: ' . $e->getMessage()], 500);
        }
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

    private function formatBooking(TestDriveBooking $booking): array
    {
        return [
            'id' => $booking->getId(),
            'status' => $booking->getStatus(),
            'requestedDateTime' => $booking->getRequestedDateTime()?->format('Y-m-d H:i:s'),
            'notes' => $booking->getNotes(),
            'staffRemarks' => $booking->getStaffRemarks(),
            'customer' => [
                'id' => $booking->getCustomer()->getId(),
                'email' => $booking->getCustomer()->getEmail(),
                'fullName' => $booking->getCustomer()->getFirstName() . ' ' . $booking->getCustomer()->getLastName(),
                'phone' => $booking->getCustomer()->getPhone(),
            ],
            'car' => [
                'id' => $booking->getCar()->getId(),
                'brand' => $booking->getCar()->getBrand(),
                'model' => $booking->getCar()->getMake(),
                'year' => $booking->getCar()->getYear(),
                'color' => $booking->getCar()->getColor(),
            ],
            'approvedBy' => $booking->getApprovedBy() ? [
                'id' => $booking->getApprovedBy()->getId(),
                'email' => $booking->getApprovedBy()->getEmail(),
                'fullName' => $booking->getApprovedBy()->getFirstName() . ' ' . $booking->getApprovedBy()->getLastName(),
            ] : null,
            'approvedAt' => $booking->getApprovedAt()?->format('Y-m-d H:i:s'),
            'createdAt' => $booking->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $booking->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
