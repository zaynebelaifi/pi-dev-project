<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\DeliveryRepository;

class DeliveryLocationStreamController extends AbstractController
{
    #[Route('/delivery/stream/{id}', name: 'app_delivery_location_stream', methods: ['GET'])]
    public function streamLocation(Request $request, $id, DeliveryRepository $deliveryRepository)
    {
        $response = new StreamedResponse(function() use ($id, $deliveryRepository) {
            // Simple SSE streamer: poll the delivery row every second and emit when changed.
            $last = null;
            $start = time();
            // run for up to 1 hour or until client disconnects
            while (!connection_aborted() && (time() - $start) < 3600) {
                $delivery = $deliveryRepository->find($id);
                if ($delivery) {
                    $data = [
                        'driver_latitude' => $delivery->getDriverLatitude(),
                        'driver_longitude' => $delivery->getDriverLongitude(),
                        'updated_at' => method_exists($delivery, 'getUpdatedAt') && $delivery->getUpdatedAt() ? $delivery->getUpdatedAt()->format(DATE_ATOM) : null,
                    ];
                    $json = json_encode($data);
                    if ($json !== $last) {
                        echo "data: {$json}\n\n";
                        if (ob_get_length()) {
                            @ob_flush();
                        }
                        flush();
                        $last = $json;
                    }
                }
                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        return $response;
    }
}
