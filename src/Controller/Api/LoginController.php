<?php

namespace App\Controller\Api;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class LoginController
{
    /**
     * Firewall json_login handles this route and returns a JWT (Lexik success handler).
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('This route should be intercepted by the firewall json_login authenticator.');
    }
}
