<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/images/cars/{file}', name: 'serve_car_image', methods: ['GET'], requirements: ['file' => '.+'])]
final class ImagesController extends AbstractController
{
    public function __invoke(string $file, ParameterBagInterface $params): BinaryFileResponse
    {
        // Get the car images directory from parameters
        $carImagesDirectory = $params->get('car_images_directory');

        // Security: Validate filename to prevent directory traversal
        if (strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
            throw new NotFoundHttpException('Invalid filename');
        }

        $filePath = $carImagesDirectory . DIRECTORY_SEPARATOR . $file;

        // Check if file exists
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Image not found');
        }

        // Create response
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', mime_content_type($filePath) ?: 'application/octet-stream');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);

        return $response;
    }
}
