<?php

namespace App\Tests\Service;

use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use App\Repository\AssignmentHistoryRepository;
use App\Repository\DeliveryManRepository;
use App\Repository\FleetCarRepository;
use App\Service\AssignmentService;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AssignmentServiceTest extends TestCase
{
    public function testAssignCarThrowsWhenCarNotAvailable(): void
    {
        $service = new AssignmentService(
            $this->createMock(AssignmentHistoryRepository::class),
            $this->createMock(DeliveryManRepository::class),
            $this->createMock(FleetCarRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(AuditService::class),
        );

        $car = (new FleetCar())
            ->setStatus('OCCUPIED')
            ->setIsActive(true);

        $deliveryMan = (new DeliveryMan())
            ->setIsAvailable(true)
            ->setCurrentCar(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CAR_NOT_AVAILABLE');

        $service->assignCar($car, $deliveryMan, null, 'manual');
    }
}
