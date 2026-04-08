<?php

namespace App\Security;

use App\Entity\User;
use App\Service\ActivityLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private ?ActivityLoggerService $activityLogger = null
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // Check if account is enabled
                    if (!$existingUser->isEnabled()) {
                        throw new AuthenticationException('Your account is suspended or inactive. Please contact an administrator.');
                    }

                    // Update last login
                    $existingUser->setLastLoginAt(new \DateTime());
                    $this->entityManager->flush();

                    return $existingUser;
                }

                // Create new user from Google data
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($this->generateUsername($googleUser));
                $user->setFirstName($googleUser->getFirstName() ?? 'Google');
                $user->setLastName($googleUser->getLastName() ?? 'User');
                $user->setPassword(password_hash(uniqid(), PASSWORD_BCRYPT)); // Random password
                $user->setIsVerified(true);
                $user->setStatus('active');
                $user->setRole('staff'); // Default role for Google sign-ups
                $user->setLastLoginAt(new \DateTime());

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Log the login activity
        if ($this->activityLogger) {
            $this->activityLogger->logLogin();
        }

        // Check if this was a popup request
        $isPopup = $request->getSession()->get('oauth_popup', false);
        
        if ($isPopup) {
            // For popup requests, return HTML that notifies parent window
            return $this->renderPopupSuccessResponse('/dashboard');
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Check if this was a popup request
        $isPopup = $request->getSession()->get('oauth_popup', false);
        
        if ($isPopup) {
            // For popup requests, return HTML that notifies parent window of error
            return $this->renderPopupErrorResponse($exception->getMessage());
        }

        $request->getSession()->set('error', $exception->getMessage());
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    /**
     * Render HTML response that sends success message to parent window
     */
    private function renderPopupSuccessResponse(string $redirectUrl): Response
    {
        $response = new Response(<<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Google Login - Success</title>
        </head>
        <body style="margin: 0; background: #f5f5f5;">
            <div style="display: flex; justify-content: center; align-items: center; height: 100vh;">
                <div style="text-align: center;">
                    <h2 style="color: #333; margin-bottom: 10px;">Success!</h2>
                    <p style="color: #666; margin: 0;">You are being logged in...</p>
                </div>
            </div>
            <script>
                // Send success message to parent window
                window.opener.postMessage({
                    type: 'OAUTH_SUCCESS',
                    redirectUrl: REDIRECT_URL_PLACEHOLDER
                }, window.location.origin);
                
                // Close popup after a short delay
                setTimeout(() => window.close(), 500);
            </script>
        </body>
        </html>
        HTML);

        // Replace placeholder with actual redirect URL
        $content = str_replace('REDIRECT_URL_PLACEHOLDER', "'" . $redirectUrl . "'", $response->getContent());
        $response->setContent($content);

        return $response;
    }

    /**
     * Render HTML response that sends error message to parent window
     */
    private function renderPopupErrorResponse(string $errorMessage): Response
    {
        $errorMessage = addslashes($errorMessage);

        $response = new Response(<<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Google Login - Error</title>
        </head>
        <body style="margin: 0; background: #f5f5f5;">
            <div style="display: flex; justify-content: center; align-items: center; height: 100vh;">
                <div style="text-align: center;">
                    <h2 style="color: #d32f2f; margin-bottom: 10px;">Login Failed</h2>
                    <p style="color: #666; margin: 0; white-space: pre-wrap;">ERROR_MESSAGE_PLACEHOLDER</p>
                </div>
            </div>
            <script>
                // Send error message to parent window
                window.opener.postMessage({
                    type: 'OAUTH_ERROR',
                    message: 'ERROR_MESSAGE_PLACEHOLDER'
                }, window.location.origin);
                
                // Close popup after a short delay
                setTimeout(() => window.close(), 3000);
            </script>
        </body>
        </html>
        HTML);

        // Replace placeholder with actual error message
        $content = str_replace('ERROR_MESSAGE_PLACEHOLDER', $errorMessage, $response->getContent());
        $response->setContent($content);

        return $response;
    }

    private function generateUsername(GoogleUser $googleUser): string
    {
        $base = strtolower($googleUser->getFirstName() . $googleUser->getLastName());
        $base = preg_replace('/[^a-z0-9]/', '', $base);
        $username = $base;
        $counter = 1;

        // Ensure username is unique
        while ($this->entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
