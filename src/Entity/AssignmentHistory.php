<?php

namespace App\Entity;

use App\Repository\AssignmentHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssignmentHistoryRepository::class)]
#[ORM\Table(name: 'assignment_history')]
class AssignmentHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FleetCar::class)]
    #[ORM\JoinColumn(name: 'car_id', referencedColumnName: 'car_id', nullable: false, onDelete: 'CASCADE')]
    private ?FleetCar $car = null;

    #[ORM\ManyToOne(targetEntity: DeliveryMan::class, inversedBy: 'assignment_history')]
    #[ORM\JoinColumn(name: 'delivery_man_id', referencedColumnName: 'delivery_man_id', nullable: false, onDelete: 'CASCADE')]
    private ?DeliveryMan $deliveryMan = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedBy = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $assigned_at = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $unassigned_at = null;

    #[ORM\Column(type: 'string', length: 40, options: ['default' => 'manual'])]
    private string $reason = 'manual';

    #[ORM\Column(type: 'string', length: 30, options: ['default' => 'active'])]
    private string $status = 'active';

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

    public function getAssignedBy(): ?User
    {
        return $this->assignedBy;
    }

    public function setAssignedBy(?User $assignedBy): self
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    public function getAssignedAt(): ?\DateTimeInterface
    {
        return $this->assigned_at;
    }

    public function setAssignedAt(?\DateTimeInterface $assignedAt): self
    {
        $this->assigned_at = $assignedAt;

        return $this;
    }

    public function getUnassignedAt(): ?\DateTimeInterface
    {
        return $this->unassigned_at;
    }

    public function setUnassignedAt(?\DateTimeInterface $unassignedAt): self
    {
        $this->unassigned_at = $unassignedAt;

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
