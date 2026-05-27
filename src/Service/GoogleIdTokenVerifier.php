<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies Google ID tokens from the mobile app (React Native Google Sign-In).
 */
final class GoogleIdTokenVerifier
{
    /** Web client ID — must match React Native GoogleSignin.configure({ webClientId }) */
    private const DEFAULT_WEB_CLIENT_ID = '220287836624-tm0ep198jig2bvdt2mtv4fom64uksqa5.apps.googleusercontent.com';

    /** Android client ID — from google-services.json (idToken azp) */
    private const DEFAULT_ANDROID_CLIENT_ID = '220287836624-0lf7m4ip4nomci3dl7hjrkertklcasqt.apps.googleusercontent.com';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $googleClientId = '',
        private string $googleAndroidClientId = '',
    ) {
    }

    /**
     * @return list<string>
     */
    private function allowedClientIds(): array
    {
        $web = trim($this->googleClientId) !== ''
            ? trim($this->googleClientId)
            : self::DEFAULT_WEB_CLIENT_ID;
        $android = trim($this->googleAndroidClientId) !== ''
            ? trim($this->googleAndroidClientId)
            : self::DEFAULT_ANDROID_CLIENT_ID;

        return array_values(array_unique([$web, $android]));
    }

    /**
     * @return array<string, mixed> Verified token claims (email, given_name, family_name, …)
     */
    public function verify(string $idToken): array
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            throw new \InvalidArgumentException('idToken is required');
        }

        $response = $this->httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
            'query' => ['id_token' => $idToken],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \InvalidArgumentException('Invalid Google ID token');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray();

        $allowed = $this->allowedClientIds();

        $aud = (string) ($payload['aud'] ?? '');
        $azp = (string) ($payload['azp'] ?? '');

        if (!in_array($aud, $allowed, true) && ($azp === '' || !in_array($azp, $allowed, true))) {
            throw new \InvalidArgumentException('Google token audience mismatch');
        }

        $emailVerified = $payload['email_verified'] ?? false;
        if ($emailVerified !== true && $emailVerified !== 'true') {
            throw new \InvalidArgumentException('Google email is not verified');
        }

        $email = (string) ($payload['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Google token is missing a valid email');
        }

        return $payload;
    }
}
