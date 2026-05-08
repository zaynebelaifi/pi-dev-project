<?php

declare(strict_types=1);

namespace App\Service\Fleet;

use App\Contract\RouteServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * RouteService — calculates routes using OSRM (free, no API key required).
 *
 * OSRM (Open Source Routing Machine) is a free routing engine using
 * OpenStreetMap data. API endpoint: https://router.project-osrm.org
 *
 * When a Google Maps API key is available in the future, swap the
 * `getRouteDataFromOSRM()` call with `getRouteDataFromGoogle()`.
 */
class RouteService implements RouteServiceInterface
{
    /** Base URL for the public OSRM demo server. */
    private const OSRM_BASE = 'https://router.project-osrm.org/route/v1/driving';

    /** Average urban delivery speed (km/h) used for Haversine-based ETA fallback. */
    private const AVG_SPEED_KMH = 25.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger,
        private readonly string              $apiKey = '',  // Optional — for future Google upgrade
    ) {}

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * Get routing data between two coordinate pairs.
     *
     * Returns:
     *  - distance: metres
     *  - duration: seconds
     *  - distanceKm: rounded km label
     *  - durationMin: ETA in minutes
     *  - polyline: encoded polyline string (for Leaflet decoding)
     *  - steps: turn-by-turn steps (optional, for future nav feature)
     */
    public function getRouteData(float $startLat, float $startLng, float $endLat, float $endLng): array
    {
        try {
            return $this->getRouteFromOSRM($startLat, $startLng, $endLat, $endLng);
        } catch (\Throwable $e) {
            $this->logger->warning('[Route] OSRM failed, falling back to Haversine: ' . $e->getMessage());
            return $this->haversineFallback($startLat, $startLng, $endLat, $endLng);
        }
    }

    /**
     * Get ETA in minutes between two coordinate pairs.
     */
    public function getETA(float $startLat, float $startLng, float $endLat, float $endLng): int
    {
        $route = $this->getRouteData($startLat, $startLng, $endLat, $endLng);
        return $route['durationMin'] ?? $this->haversineFallback($startLat, $startLng, $endLat, $endLng)['durationMin'];
    }

    /**
     * Required by interface — string-based address version (delegates to coord version).
     */
    public function getRouteData_byAddress(string $origin, string $destination): array
    {
        // Address geocoding without a paid API requires Nominatim — implement later.
        // For now, return a structured empty result.
        return ['distance' => 0, 'duration' => 0, 'distanceKm' => '0 km', 'durationMin' => 0];
    }

    // ── Private: OSRM Integration ────────────────────────────────────────────

    /**
     * Fetch route from the OSRM public demo server.
     * Request format: /route/v1/driving/{lng1},{lat1};{lng2},{lat2}
     * (Note OSRM uses lng,lat order!)
     */
    private function getRouteFromOSRM(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        $url = sprintf(
            '%s/%s,%s;%s,%s',
            self::OSRM_BASE,
            $lng1, $lat1,   // OSRM: longitude first!
            $lng2, $lat2
        );

        $response = $this->httpClient->request('GET', $url, [
            'query'   => ['overview' => 'full', 'geometries' => 'polyline'],
            'timeout' => 5,
        ]);

        $data = $response->toArray();

        if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'])) {
            throw new \RuntimeException('OSRM returned no routes.');
        }

        $route    = $data['routes'][0];
        $leg      = $route['legs'][0] ?? [];
        $distM    = (int) ($route['distance'] ?? 0);   // metres
        $durSec   = (int) ($route['duration'] ?? 0);   // seconds
        $polyline = $route['geometry'] ?? '';

        return [
            'distance'    => $distM,
            'duration'    => $durSec,
            'distanceKm'  => round($distM / 1000, 1) . ' km',
            'durationMin' => (int) ceil($durSec / 60),
            'polyline'    => $polyline,
            'steps'       => array_map(
                fn($s) => $s['maneuver']['instruction'] ?? '',
                $leg['steps'] ?? []
            ),
        ];
    }

    // ── Private: Haversine fallback ──────────────────────────────────────────

    /**
     * Compute straight-line distance and estimated time using the Haversine formula.
     * Used when OSRM is unavailable.
     */
    private function haversineFallback(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        $R    = 6371000.0; // metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $distM  = (int) round(2 * $R * atan2(sqrt($a), sqrt(1 - $a)));
        $distKm = $distM / 1000;
        $durMin = (int) ceil($distKm / self::AVG_SPEED_KMH * 60);

        return [
            'distance'    => $distM,
            'duration'    => $durMin * 60,
            'distanceKm'  => round($distKm, 1) . ' km',
            'durationMin' => $durMin,
            'polyline'    => '',   // No polyline without routing engine
            'steps'       => [],
        ];
    }
}
