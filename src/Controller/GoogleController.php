<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
    ) {
    }

    #[Route(path: '/connect/google', name: 'connect_google_start')]
    public function connect(Request $request, SessionInterface $session): Response
    {
        $isPopup = $request->query->get('popup') === '1';
        
        // Store popup flag in session so we can retrieve it after OAuth callback
        $session->set('oauth_popup', $isPopup);
        
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');

        return $client->redirect([
            'openid',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ], []);
    }

    #[Route(path: '/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(Request $request, SessionInterface $session): Response
    {
        // This route is handled by GoogleAuthenticator
        // The authenticator's onAuthenticationSuccess/Failure methods will handle the response
        
        // Fallback in case authenticator doesn't handle it
        return $this->redirectToRoute('app_dashboard');
    }
}
