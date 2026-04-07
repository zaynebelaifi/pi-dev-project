<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FleetCarRepository;

#[ORM\Entity(repositoryClass: FleetCarRepository::class)]
#[ORM\Table(name: 'fleet_car')]
class FleetCar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $car_id = null;

    public function getCar_id(): ?int
    {
        return $this->car_id;
    }

    public function setCar_id(int $car_id): self
    {
        $this->car_id = $car_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $make = null;

    public function getMake(): ?string
    {
        return $this->make;
    }

    public function setMake(string $make): self
    {
        $this->make = $make;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $model = null;

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $license_plate = null;

    public function getLicense_plate(): ?string
    {
        return $this->license_plate;
    }

    public function setLicense_plate(string $license_plate): self
    {
        $this->license_plate = $license_plate;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $vehicle_type = null;

    public function getVehicle_type(): ?string
    {
        return $this->vehicle_type;
    }

    public function setVehicle_type(string $vehicle_type): self
    {
        $this->vehicle_type = $vehicle_type;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $delivery_man_id = null;

    public function getDelivery_man_id(): ?int
    {
        return $this->delivery_man_id;
    }

    public function setDelivery_man_id(?int $delivery_man_id): self
    {
        $this->delivery_man_id = $delivery_man_id;
        return $this;
    }

    // Symfony PropertyAccessor camelCase aliases for snake_case properties
    public function getId(): ?int { return $this->getCar_id(); }
    public function setId(int $id): self { return $this->setCar_id($id); }
    
    public function getCarId(): ?int { return $this->getCar_id(); }
    public function setCarId(int $id): self { return $this->setCar_id($id); }
    
    public function getLicensePlate(): ?string { return $this->getLicense_plate(); }
    public function setLicensePlate(string $plate): self { return $this->setLicense_plate($plate); }
    
    public function getVehicleType(): ?string { return $this->getVehicle_type(); }
    public function setVehicleType(string $type): self { return $this->setVehicle_type($type); }
    
    public function getDeliveryManId(): ?int { return $this->getDelivery_man_id(); }
    public function setDeliveryManId(?int $id): self { return $this->setDelivery_man_id($id); }
}
