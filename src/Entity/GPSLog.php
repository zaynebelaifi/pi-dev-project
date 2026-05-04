<?php

namespace App\Entity;

use App\Repository\GPSLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GPSLogRepository::class)]
#[ORM\Table(name: 'gps_log')]
#[ORM\Index(name: 'idx_gps_log_car_timestamp', columns: ['car_id', 'timestamp'])]
class GPSLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FleetCar::class)]
    #[ORM\JoinColumn(name: 'car_id', referencedColumnName: 'car_id', nullable: false, onDelete: 'CASCADE')]
    private ?FleetCar $car = null;

    #[ORM\ManyToOne(targetEntity: DeliveryMan::class, inversedBy: 'gps_logs')]
    #[ORM\JoinColumn(name: 'delivery_man_id', referencedColumnName: 'delivery_man_id', nullable: true, onDelete: 'SET NULL')]
    private ?DeliveryMan $deliveryMan = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $accuracy = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $altitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $speed = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $bearing = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(type: 'string', length: 30, options: ['default' => 'gps'])]
    private string $source = 'gps';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): ?FleetCar
    {
        return $this->car;
    }

    public function setCar(?FleetCar $car): self
    {
        $this->car = $car;

        return $this;
    }

    public function getDeliveryMan(): ?DeliveryMan
    {
        return $this->deliveryMan;
    }

    public function setDeliveryMan(?DeliveryMan $deliveryMan): self
    {
        $this->deliveryMan = $deliveryMan;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getAccuracy(): ?int
    {
        return $this->accuracy;
    }

    public function setAccuracy(?int $accuracy): self
    {
        $this->accuracy = $accuracy;

        return $this;
    }

    public function getAltitude(): ?float
    {
        return $this->altitude;
    }

    public function setAltitude(?float $altitude): self
    {
        $this->altitude = $altitude;

        return $this;
    }

    public function getSpeed(): ?float
    {
        return $this->speed;
    }

    public function setSpeed(?float $speed): self
    {
        $this->speed = $speed;

        return $this;
    }

    public function getBearing(): ?float
    {
        return $this->bearing;
    }

    public function setBearing(?float $bearing): self
    {
        $this->bearing = $bearing;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }
}
