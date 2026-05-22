<?php

namespace App\Controller\Dev;

use App\Service\RailwayDatabaseSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Push local MySQL data to Railway (dev machine only).
 *
 * Set in .env.local:
 *   RAILWAY_DATABASE_URL=mysql://user:pass@host:port/railway
 *   SYNC_SECRET=choose-a-long-random-string
 */
#[Route('/dev/sync-railway')]
#[IsGranted('ROLE_ADMIN')]
final class RailwaySyncController extends AbstractController
{
    public function __construct(
        private RailwayDatabaseSyncService $syncService,
        private string $syncSecret = '',
    ) {}

    #[Route('', name: 'dev_sync_railway', methods: ['GET', 'POST'])]
    public function sync(Request $request): Response
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException();
        }

        if (!$this->syncService->isConfigured()) {
            return $this->render('dev/railway_sync.html.twig', [
                'configured' => false,
                'result' => null,
                'error' => null,
            ]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('sync_railway', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid security token.');

                return $this->redirectToRoute('dev_sync_railway');
            }

            $secret = (string) $request->request->get('sync_secret', '');
            if ($this->syncSecret === '' || !hash_equals($this->syncSecret, $secret)) {
                $this->addFlash('error', 'Invalid sync secret.');

                return $this->redirectToRoute('dev_sync_railway');
            }

            if ($request->request->get('confirm') !== 'yes') {
                $this->addFlash('error', 'You must confirm the overwrite.');

                return $this->redirectToRoute('dev_sync_railway');
            }

            try {
                $result = $this->syncService->sync();
                $this->addFlash('success', sprintf(
                    'Sync complete: %d rows copied to Railway.',
                    $result['total']
                ));

                return $this->render('dev/railway_sync.html.twig', [
                    'configured' => true,
                    'result' => $result,
                    'error' => null,
                ]);
            } catch (\Throwable $e) {
                return $this->render('dev/railway_sync.html.twig', [
                    'configured' => true,
                    'result' => null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->render('dev/railway_sync.html.twig', [
            'configured' => true,
            'result' => null,
            'error' => null,
        ]);
    }
}
