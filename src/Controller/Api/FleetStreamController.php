<?php

namespace App\Controller\Api;

use App\Contract\FleetServiceInterface;
use App\Repository\FleetAlertRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * SSE endpoint for live fleet tracking updates.
 */
class FleetStreamController extends AbstractController
{
    public function __construct(
        private readonly FleetServiceInterface $fleetService,
        private readonly FleetAlertRepository  $alertRepo,
    ) {}

    #[Route('/api/fleet/stream', name: 'api_fleet_stream')]
    public function streamEvents(): Response
    {
        $response = new StreamedResponse(function (): void {
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $status = $this->fleetService->getFleetStatus();
                $alerts = $this->alertRepo->findRecentUnacknowledged(5);

                $payload = json_encode([
                    'drivers'   => $status['drivers'] ?? [],
                    'alerts'    => array_map(fn($a) => [
                        'id'       => $a->getId(),
                        'type'     => $a->getType(),
                        'message'  => $a->getMessage(),
                        'severity' => $a->getSeverity(),
                        'time'     => $a->getCreatedAt()->format('H:i:s'),
                    ], $alerts),
                    'timestamp' => date('c'),
                ]);

                echo "data: {$payload}\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(5);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // for Nginx

        return $response;
    }
}
