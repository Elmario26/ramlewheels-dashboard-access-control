<?php

namespace App\Controller\Api;

use App\Entity\Cars;
use App\Repository\CarsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cars', name: 'api_cars', methods: ['GET'])]
final class CarsController extends AbstractController
{
    public function __invoke(
        Request $request,
        CarsRepository $carsRepository
    ): JsonResponse {
        try {
            // Get all cars
            $cars = $carsRepository->findAll();

            // Format response
            $carsData = array_map(fn(Cars $car) => [
                'id' => $car->getId(),
                'brand' => $car->getBrand(),
                'model' => $car->getMake(),
                'year' => $car->getYear(),
                'price' => $car->getPrice(),
                'mileage' => $car->getMileage(),
                'condition' => $car->getConditions(),
                'status' => $car->getStatus(),
                'color' => $car->getColor(),
                'plateNumber' => $car->getPlateNumber(),
                'engineNumber' => $car->getEngineNumber(),
                'damageDescription' => $car->getDamageDescription(),
                'images' => array_map(fn($image) => [
                    'filename' => $image,
                    'url' => '/images/cars/' . $image,
                ], $car->getImages()),
            ], $cars);

            return $this->json([
                'success' => true,
                'count' => count($carsData),
                'data' => $carsData,
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch cars: ' . $e->getMessage()], 500);
        }
    }
}
