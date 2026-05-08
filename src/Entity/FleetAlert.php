<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FleetAlertRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Persistent alert record for geofence violations, delays, and anomalies.
 */
#[ORM\Entity(repositoryClass: FleetAlertRepository::class)]
#[ORM\Table(name: 'fleet_alert')]
class FleetAlert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /** The driver who triggered the alert. */
    #[ORM\ManyToOne(targetEntity: DeliveryMan::class)]
    #[ORM\JoinColumn(name: 'delivery_man_id', referencedColumnName: 'delivery_man_id', nullable: true)]
    private ?DeliveryMan $deliveryMan = null;

    /** The delivery related to this alert (optional). */
    #[ORM\ManyToOne(targetEntity: Delivery::class)]
    #[ORM\JoinColumn(name: 'delivery_id', referencedColumnName: 'delivery_id', nullable: true)]
    private ?Delivery $delivery = null;

    /** Alert category: geofence | delay | fuel | weather | manual. */
    #[ORM\Column(type: 'string', length: 30)]
    private string $type = 'manual';

    /** Human-readable alert message. */
    #[ORM\Column(type: 'text')]
    private string $message = '';

    /** Severity: info | warning | critical. */
    #[ORM\Column(type: 'string', length: 15, options: ['default' => 'warning'])]
    private string $severity = 'warning';

    /** Whether an admin has acknowledged this alert. */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $acknowledged = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDeliveryMan(): ?DeliveryMan { return $this->deliveryMan; }
    public function setDeliveryMan(?DeliveryMan $deliveryMan): self { $this->deliveryMan = $deliveryMan; return $this; }

    public function getDelivery(): ?Delivery { return $this->delivery; }
    public function setDelivery(?Delivery $delivery): self { $this->delivery = $delivery; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function getSeverity(): string { return $this->severity; }
    public function setSeverity(string $severity): self { $this->severity = $severity; return $this; }

    public function isAcknowledged(): bool { return $this->acknowledged; }
    public function setAcknowledged(bool $acknowledged): self { $this->acknowledged = $acknowledged; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
