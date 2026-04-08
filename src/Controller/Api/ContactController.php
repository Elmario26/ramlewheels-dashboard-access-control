<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ContactController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $brevoApiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $brevoApiKey = ''
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->brevoApiKey = $brevoApiKey;
    }

    #[Route('/api/contact', name: 'api_contact_submit', methods: ['POST'])]
    public function submitContact(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], 400);
            }

            // Validate required fields
            $required = ['name', 'email', 'phone', 'message'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->json(['error' => "Field '$field' is required"], 400);
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Invalid email format'], 400);
            }

            // Send to Brevo
            $result = $this->sendToBrevo($data);
            
            if ($result) {
                return $this->json([
                    'success' => true,
                    'message' => 'Contact form submitted successfully'
                ]);
            } else {
                return $this->json([
                    'error' => 'Failed to send message. Please try again.'
                ], 500);
            }

        } catch (\Exception $e) {
            $this->logger->error('Contact form submission error: ' . $e->getMessage());
            return $this->json([
                'error' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    private function sendToBrevo(array $data): bool
    {
        if (empty($this->brevoApiKey)) {
            $this->logger->error('Brevo API key is not configured');
            return false;
        }

        try {
            // Create/update contact in Brevo
            $contactResponse = $this->httpClient->request('POST', 'https://api.brevo.com/v3/contacts', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'api-key' => $this->brevoApiKey,
                ],
                'json' => [
                    'email' => $data['email'],
                    'attributes' => [
                        'FIRSTNAME' => $this->extractFirstName($data['name']),
                        'LASTNAME' => $this->extractLastName($data['name']),
                        'PHONE' => $data['phone'],
                        'INTEREST' => $data['interest'] ?? '',
                        'MESSAGE' => $data['message'],
                        'SOURCE' => 'Website Contact Form',
                    ],
                    'listIds' => [], // Add your list IDs here if you want to add to specific lists
                    'updateEnabled' => true,
                ],
            ]);

            $statusCode = $contactResponse->getStatusCode();
            
            // 201 = created, 204 = updated
            if ($statusCode === 201 || $statusCode === 204 || $statusCode === 200) {
                $this->logger->info('Contact added/updated in Brevo: ' . $data['email']);
                
                // Send confirmation to user
                $this->sendConfirmationEmail($data);
                
                // Send notification to dealership
                $this->sendDealershipNotification($data);
                
                return true;
            }

            $this->logger->error('Brevo API returned status: ' . $statusCode);
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Brevo API error: ' . $e->getMessage());
            return false;
        }
    }

    private function sendConfirmationEmail(array $data): void
    {
        try {
            $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'api-key' => $this->brevoApiKey,
                ],
                'json' => [
                    'sender' => [
                        'name' => 'Ramle Wheels',
                        'email' => 'ramlelariosa26@gmail.com',
                    ],
                    'to' => [
                        [
                            'email' => $data['email'],
                            'name' => $data['name'],
                        ]
                    ],
                    'subject' => 'Thank you for contacting Ramle Wheels',
                    'htmlContent' => $this->buildEmailTemplate($data),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to send confirmation email: ' . $e->getMessage());
            // Log more details for debugging
            $this->logger->error('Confirmation email error details: ' . $e->getTraceAsString());
        }
    }

    private function buildEmailTemplate(array $data): string
    {
        return sprintf(
            '<html><body>' .
            '<h2>Thank you for contacting Ramle Wheels!</h2>' .
            '<p>Dear %s,</p>' .
            '<p>We have received your inquiry and will get back to you within 24 hours.</p>' .
            '<h3>Your Message:</h3>' .
            '<p><strong>Interest:</strong> %s</p>' .
            '<p><strong>Message:</strong> %s</p>' .
            '<br>' .
            '<p>Best regards,<br>Ramle Wheels Team</p>' .
            '</body></html>',
            htmlspecialchars($data['name']),
            htmlspecialchars($data['interest'] ?: 'Not specified'),
            nl2br(htmlspecialchars($data['message']))
        );
    }

    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }

    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) > 1) {
            array_shift($parts);
            return implode(' ', $parts);
        }
        return '';
    }

    private function sendDealershipNotification(array $data): void
    {
        try {
            $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'api-key' => $this->brevoApiKey,
                ],
                'json' => [
                    'sender' => [
                        'name' => 'Ramle Wheels Website',
                        'email' => 'noreply@ramlewheels.com',
                    ],
                    'to' => [
                        [
                            'email' => 'ramlelariosa26@gmail.com', // TODO: Replace with your actual dealership email
                            'name' => 'Ramle Wheels Team',
                        ]
                    ],
                    'subject' => 'New Contact Form Submission - ' . $data['name'],
                    'htmlContent' => $this->buildNotificationTemplate($data),
                ],
            ]);
            
            $this->logger->info('Dealership notification sent for: ' . $data['email']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send dealership notification: ' . $e->getMessage());
        }
    }

    private function buildNotificationTemplate(array $data): string
    {
        return sprintf(
            '<html><body style="font-family: Arial, sans-serif; line-height: 1.6;">' .
            '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">' .
            '<h2 style="color: #f97316;">New Contact Form Submission</h2>' .
            '<p><strong>Name:</strong> %s</p>' .
            '<p><strong>Email:</strong> %s</p>' .
            '<p><strong>Phone:</strong> %s</p>' .
            '<p><strong>Vehicle Interest:</strong> %s</p>' .
            '<h3>Message:</h3>' .
            '<p style="background: #f3f4f6; padding: 15px; border-radius: 8px;">%s</p>' .
            '<hr>' .
            '<p style="color: #6b7280; font-size: 0.9em;">Submitted at: %s</p>' .
            '</div></body></html>',
            htmlspecialchars($data['name']),
            htmlspecialchars($data['email']),
            htmlspecialchars($data['phone']),
            htmlspecialchars($data['interest'] ?: 'Not specified'),
            nl2br(htmlspecialchars($data['message'])),
            date('Y-m-d H:i:s')
        );
    }
}
