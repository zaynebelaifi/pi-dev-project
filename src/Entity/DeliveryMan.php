<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DeliveryManRepository;

#[ORM\Entity(repositoryClass: DeliveryManRepository::class)]
#[ORM\Table(name: 'delivery_man')]
class DeliveryMan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $delivery_man_id = null;

    public function getDelivery_man_id(): ?int
    {
        return $this->delivery_man_id;
    }

    public function setDelivery_man_id(int $delivery_man_id): self
    {
        $this->delivery_man_id = $delivery_man_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $phone = null;

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $vehicle_type = null;

    public function getVehicle_type(): ?string
    {
        return $this->vehicle_type;
    }

    public function setVehicle_type(?string $vehicle_type): self
    {
        $this->vehicle_type = $vehicle_type;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $vehicle_number = null;

    public function getVehicle_number(): ?string
    {
        return $this->vehicle_number;
    }

    public function setVehicle_number(?string $vehicle_number): self
    {
        $this->vehicle_number = $vehicle_number;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $address = null;

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $salary = null;

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(?float $salary): self
    {
        $this->salary = $salary;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_of_joining = null;

    public function getDate_of_joining(): ?\DateTimeInterface
    {
        return $this->date_of_joining;
    }

    public function setDate_of_joining(?\DateTimeInterface $date_of_joining): self
    {
        $this->date_of_joining = $date_of_joining;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $rating = null;

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $updated_at = null;

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'deliveryMan')]
    private Collection $deliverys;

    /**
     * @return Collection<int, Delivery>
     */
    public function getDeliverys(): Collection
    {
        if (!$this->deliverys instanceof Collection) {
            $this->deliverys = new ArrayCollection();
        }
        return $this->deliverys;
    }

    public function addDelivery(Delivery $delivery): self
    {
        if (!$this->getDeliverys()->contains($delivery)) {
            $this->getDeliverys()->add($delivery);
        }
        return $this;
    }

    public function removeDelivery(Delivery $delivery): self
    {
        $this->getDeliverys()->removeElement($delivery);
        return $this;
    }

}
