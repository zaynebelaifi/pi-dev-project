<?php

namespace App\Service;

use App\Entity\FleetCar;
use App\Repository\FleetCarRepository;
use Doctrine\ORM\EntityManagerInterface;

class CarService
{
    public function __construct(
        private FleetCarRepository $fleetCarRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return FleetCar[]
     */
    public function getAllCars(): array
    {
        return $this->fleetCarRepository->findAll();
    }

    /**
     * @return FleetCar[]
     */
    public function getAvailableCars(): array
    {
        return $this->fleetCarRepository->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->andWhere('c.is_active = :isActive')
            ->setParameter('status', 'AVAILABLE')
            ->setParameter('isActive', true)
            ->orderBy('c.car_id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function createCar(FleetCar $car): FleetCar
    {
        if ($car->getCreatedAt() === null) {
            $car->setCreatedAt(new \DateTimeImmutable());
        }

        $car->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($car);
        $this->entityManager->flush();

        return $car;
    }

    public function updateCar(FleetCar $car): FleetCar
    {
        $car->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $car;
    }

    public function deleteCar(FleetCar $car): void
    {
        $this->entityManager->remove($car);
        $this->entityManager->flush();
    }
}
