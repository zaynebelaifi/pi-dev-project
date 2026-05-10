<?php

namespace App\Service;

use App\Entity\AssignmentHistory;
use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use App\Entity\User;
use App\Repository\AssignmentHistoryRepository;
use App\Repository\DeliveryManRepository;
use App\Repository\FleetCarRepository;
use Doctrine\ORM\EntityManagerInterface;

class AssignmentService
{
    public function __construct(
        private AssignmentHistoryRepository $assignmentHistoryRepository,
        private DeliveryManRepository $deliveryManRepository,
        private FleetCarRepository $fleetCarRepository,
        private EntityManagerInterface $entityManager,
        private AuditService $auditService,
    ) {
    }

    public function assignCar(FleetCar $car, DeliveryMan $deliveryMan, ?User $assignedBy = null, string $reason = 'manual'): AssignmentHistory
    {
        if (!$car->isActive() || strtoupper($car->getStatus()) !== 'AVAILABLE') {
            throw new \RuntimeException('CAR_NOT_AVAILABLE');
        }

        if (!$deliveryMan->isAvailable() || $deliveryMan->getCurrentCar() !== null) {
            throw new \RuntimeException('DELIVERY_MAN_NOT_AVAILABLE');
        }

        $history = (new AssignmentHistory())
            ->setCar($car)
            ->setDeliveryMan($deliveryMan)
            ->setAssignedBy($assignedBy)
            ->setAssignedAt(new \DateTimeImmutable())
            ->setReason($reason)
            ->setStatus('active');

        $deliveryMan->setCurrentCar($car)->setIsAvailable(false);
        $car->setStatus('OCCUPIED')->setDeliveryManId($deliveryMan->getDeliveryManId());

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        $this->auditService->logAction(
            $assignedBy,
            'ASSIGN',
            'FleetCar',
            (int) $car->getCarId(),
            [
                'deliveryManId' => $deliveryMan->getDeliveryManId(),
                'reason' => $reason,
            ]
        );

        return $history;
    }

    public function unassignCar(FleetCar $car, ?User $actor = null): void
    {
        $active = $this->assignmentHistoryRepository->findActiveByCar($car);
        if (!$active instanceof AssignmentHistory) {
            return;
        }

        $deliveryMan = $active->getDeliveryMan();
        if ($deliveryMan instanceof DeliveryMan) {
            $deliveryMan->setCurrentCar(null)->setIsAvailable(true);
        }

        $active->setStatus('completed')->setUnassignedAt(new \DateTimeImmutable());
        $car->setStatus('AVAILABLE')->setDeliveryManId(null);

        $this->entityManager->flush();

        $this->auditService->logAction(
            $actor,
            'UNASSIGN',
            'FleetCar',
            (int) $car->getCarId(),
            [
                'previousDeliveryManId' => $deliveryMan?->getDeliveryManId(),
            ]
        );
    }

    /**
     * @return AssignmentHistory[]
     */
    public function autoAssign(?User $actor = null): array
    {
        $cars = $this->fleetCarRepository->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->andWhere('c.is_active = :isActive')
            ->setParameter('status', 'AVAILABLE')
            ->setParameter('isActive', true)
            ->orderBy('c.car_id', 'ASC')
            ->getQuery()
            ->getResult();

        $deliveryMen = $this->deliveryManRepository->createQueryBuilder('d')
            ->andWhere('d.is_available = :isAvailable')
            ->andWhere('d.currentCar IS NULL')
            ->setParameter('isAvailable', true)
            ->orderBy('d.delivery_man_id', 'ASC')
            ->getQuery()
            ->getResult();

        $assignments = [];
        $pairCount = min(count($cars), count($deliveryMen));

        for ($i = 0; $i < $pairCount; $i++) {
            $assignments[] = $this->assignCar($cars[$i], $deliveryMen[$i], $actor, 'auto-assign');
        }

        return $assignments;
    }

    public function unassign(AssignmentHistory $history, ?User $actor = null, string $reason = 'manual'): void
    {
        $this->unassignCar($history->getCar(), $actor);
    }

    public function complete(AssignmentHistory $history, ?User $actor = null): void
    {
        $deliveryMan = $history->getDeliveryMan();
        if ($deliveryMan instanceof DeliveryMan) {
            $deliveryMan->setCurrentCar(null)->setIsAvailable(true);
        }

        $car = $history->getCar();
        if ($car instanceof FleetCar) {
            $car->setStatus('AVAILABLE')->setDeliveryManId(null);
        }

        $history->setStatus('completed')->setUnassignedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->auditService->logAction(
            $actor,
            'COMPLETE_ASSIGNMENT',
            'AssignmentHistory',
            (int) $history->getId(),
            []
        );
    }
}
