<?php

namespace App\Controller\Api;

use App\Service\ApiGoogleLoginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleLoginController extends AbstractController
{
    #[Route('/api/login/google', name: 'api_login_google', methods: ['POST'])]
    public function login(Request $request, ApiGoogleLoginService $googleLogin): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON body'], 400);
        }

        $idToken = trim((string) ($data['idToken'] ?? ''));
        if ($idToken === '') {
            return $this->json(['message' => 'idToken is required'], 400);
        }

        try {
            return $this->json($googleLogin->login($idToken));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 401);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 403);
        } catch (\Throwable) {
            return $this->json(['message' => 'Google login failed'], 500);
        }
    }
}
