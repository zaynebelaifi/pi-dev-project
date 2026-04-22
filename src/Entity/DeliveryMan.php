<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use App\Repository\DeliveryManRepository;

#[ORM\Entity(repositoryClass: DeliveryManRepository::class)]
#[ORM\Table(name: 'delivery_man')]
#[UniqueEntity(fields: ['phone'], message: 'This phone number is already used.')]
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
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Name must be at least {{ limit }} characters long.', maxMessage: 'Name cannot be longer than {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/^[a-zA-Z\s\-\.\']+$/', message: 'Name can only contain letters, spaces, hyphens, dots, and apostrophes.')]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false, unique: true)]
    #[Assert\NotBlank(message: 'Phone number is required.')]
    #[Assert\Regex(pattern: '/^\d{8}$/', message: 'Phone number must be exactly 8 digits.')]
    private ?string $phone = null;

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 255, maxMessage: 'Email address cannot be longer than {{ limit }} characters.')]
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
    #[Assert\Choice(choices: ['motorcycle', 'car', 'bicycle', 'scooter', 'van', 'truck', 'other'], message: 'Please select a valid vehicle type.')]
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
    #[Assert\Length(max: 20, maxMessage: 'Vehicle number cannot be longer than {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/^[A-Z0-9\-\s]+$/i', message: 'Vehicle number can only contain letters, numbers, spaces, and hyphens.')]
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
    #[Assert\Choice(choices: ['active', 'inactive', 'on_leave', 'suspended'], message: 'Please select a valid status.')]
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
    #[Assert\Length(max: 500, maxMessage: 'Address cannot be longer than {{ limit }} characters.')]
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
    #[Assert\Positive(message: 'Salary must be greater than zero.')]
    #[Assert\LessThanOrEqual(value: 999999.99, message: 'Salary cannot exceed {{ compared_value }}.')]
    private ?string $salary = null;

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(?string $salary): self
    {
        $this->salary = $salary;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\LessThanOrEqual(value: 'today', message: 'Date of joining cannot be in the future.')]
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
    #[Assert\Range(min: 0, max: 5, notInRangeMessage: 'Rating must be between {{ min }} and {{ max }}.')]
    private ?string $rating = null;

    public function getRating(): ?string
    {
        return $this->rating;
    }

    public function setRating(?float $rating): self
    {
        $this->rating = $rating !== null ? (string) $rating : null;
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

    // Symfony PropertyAccessor camelCase aliases for snake_case properties
    public function getId(): ?int { return $this->getDelivery_man_id(); }
    public function setId(int $id): self { return $this->setDelivery_man_id($id); }
    
    public function getDeliveryManId(): ?int { return $this->getDelivery_man_id(); }
    public function setDeliveryManId(int $id): self { return $this->setDelivery_man_id($id); }
    
    public function getVehicleType(): ?string { return $this->getVehicle_type(); }
    public function setVehicleType(?string $type): self { return $this->setVehicle_type($type); }
    
    public function getVehicleNumber(): ?string { return $this->getVehicle_number(); }
    public function setVehicleNumber(?string $num): self { return $this->setVehicle_number($num); }
    
    public function getDateOfJoining(): ?\DateTimeInterface { return $this->getDate_of_joining(); }
    public function setDateOfJoining(?\DateTimeInterface $date): self { return $this->setDate_of_joining($date); }
    
    public function getCreatedAt(): ?\DateTimeInterface { return $this->getCreated_at(); }
    public function setCreatedAt(\DateTimeInterface $at): self { return $this->setCreated_at($at); }
    
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->getUpdated_at(); }
    public function setUpdatedAt(\DateTimeInterface $at): self { return $this->setUpdated_at($at); }
}
