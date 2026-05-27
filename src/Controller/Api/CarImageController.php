<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public car photos for the mobile app (same files as dashboard uploads).
 */
#[Route('/api/car-images/{file}', name: 'api_car_image', methods: ['GET'], requirements: ['file' => '.+'])]
final class CarImageController extends AbstractController
{
    public function __invoke(string $file, ParameterBagInterface $params): Response
    {
        if (str_contains($file, '..') || str_contains($file, '/') || str_contains($file, '\\')) {
            throw new NotFoundHttpException('Invalid filename');
        }

        $directory = $params->get('car_images_directory');
        $path = $directory . DIRECTORY_SEPARATOR . $file;

        if (!is_file($path)) {
            throw new NotFoundHttpException('Image not found');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', mime_content_type($path) ?: 'image/jpeg');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file);
        $response->setPublic();
        $response->setMaxAge(604800);

        return $response;
    }
}
