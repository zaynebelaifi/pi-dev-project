<?php
namespace App\Service;

use App\Entity\Delivery;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class LogisticsService
{
    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private string $orsKey = ''
    ){
        if (!$this->orsKey) {
            $this->orsKey = $_ENV['ORS_API_KEY'] ?? '';
        }
    }

    /**
     * Calculate ETA and distance using OpenRouteService (or similar).
     * Returns ['duration' => seconds, 'distance' => meters]
     */
    public function calculateETA(Delivery $delivery): array
    {
        $originLat = $_ENV['RESTAURANT_LAT'] ?? null;
        $originLon = $_ENV['RESTAURANT_LON'] ?? null;

        $destLat = $delivery->getCurrentLatitude() ?: $delivery->getDeliveryNotes();
        $destLon = $delivery->getCurrentLongitude() ?: null;

        if (!$originLat || !$originLon) {
            $this->logger->warning('Restaurant coordinates not configured');
            return ['duration' => null, 'distance' => null];
        }

        $from = [(float) $originLon, (float) $originLat];
        $to = [(float) ($delivery->getCurrent_longitude() ?? $delivery->getCurrentLongitude() ?? 0), (float) ($delivery->getCurrent_latitude() ?? $delivery->getCurrentLatitude() ?? 0)];

        if (empty($to[0]) || empty($to[1])) {
            // try recipient coordinates if saved in notes (fallback)
            return ['duration' => null, 'distance' => null];
        }

        // OpenRouteService directions
        try {
            $resp = $this->http->request('POST', 'https://api.openrouteservice.org/v2/directions/driving-car/geojson', [
                'headers' => ['Authorization' => $this->orsKey, 'Content-Type' => 'application/json'],
                'json' => ['coordinates' => [ $from, $to ] ],
                'timeout' => 6,
            ]);
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                $data = $resp->toArray(false);
                $props = $data['features'][0]['properties']['summary'] ?? null;
                if ($props) {
                    return ['duration' => (int) ($props['duration'] ?? 0), 'distance' => (int) ($props['distance'] ?? 0)];
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('LogisticsService error: '.$e->getMessage());
        }

        return ['duration' => null, 'distance' => null];
    }

    public function isWithinRadius(float $lat1, float $lon1, float $lat2, float $lon2, float $meters=50): bool
    {
        // Haversine
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $dist = $earthRadius * $c;
        return $dist <= $meters;
    }
}
