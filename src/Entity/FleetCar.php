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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $color = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $year = null;

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $fuel_type = null;

    public function getFuelType(): ?string
    {
        return $this->fuel_type;
    }

    public function setFuelType(?string $fuelType): self
    {
        $this->fuel_type = $fuelType;

        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mileage = null;

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): self
    {
        $this->mileage = $mileage;

        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $registration_date = null;

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registration_date;
    }

    public function setRegistrationDate(?\DateTimeInterface $registrationDate): self
    {
        $this->registration_date = $registrationDate;

        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $last_maintenance_date = null;

    public function getLastMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->last_maintenance_date;
    }

    public function setLastMaintenanceDate(?\DateTimeInterface $lastMaintenanceDate): self
    {
        $this->last_maintenance_date = $lastMaintenanceDate;

        return $this;
    }

    #[ORM\Column(type: 'string', length: 30, options: ['default' => 'AVAILABLE'])]
    private string $status = 'AVAILABLE';

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper(trim($status));

        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    private ?float $latitude = null;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    private ?float $longitude = null;

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $last_update = null;

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->last_update;
    }

    public function setLastUpdate(?\DateTimeInterface $lastUpdate): self
    {
        $this->last_update = $lastUpdate;

        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $battery_level = null;

    public function getBatteryLevel(): ?int
    {
        return $this->battery_level;
    }

    public function setBatteryLevel(?int $batteryLevel): self
    {
        $this->battery_level = $batteryLevel;

        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fuel_level = null;

    public function getFuelLevel(): ?int
    {
        return $this->fuel_level;
    }

    public function setFuelLevel(?int $fuelLevel): self
    {
        $this->fuel_level = $fuelLevel;

        return $this;
    }

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $is_active = true;

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->is_active = $isActive;

        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->created_at = $createdAt;

        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    #[ORM\OneToMany(targetEntity: DeliveryMan::class, mappedBy: 'currentCar')]
    private Collection $assigned_delivery_men;

    /**
     * @return Collection<int, DeliveryMan>
     */
    public function getAssignedDeliveryMen(): Collection
    {
        if (!$this->assigned_delivery_men instanceof Collection) {
            $this->assigned_delivery_men = new ArrayCollection();
        }

        return $this->assigned_delivery_men;
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

    public function getBrand(): ?string { return $this->getMake(); }
    public function setBrand(string $brand): self { return $this->setMake($brand); }
}
