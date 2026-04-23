<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\DeliveryRepository;

#[ORM\Entity(repositoryClass: DeliveryRepository::class)]
#[ORM\Table(name: 'delivery')]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $delivery_id = null;

    public function getDelivery_id(): ?int
    {
        return $this->delivery_id;
    }

    public function setDelivery_id(int $delivery_id): self
    {
        $this->delivery_id = $delivery_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $order_id = null;

    public function getOrder_id(): ?int
    {
        return $this->order_id;
    }

    public function setOrder_id(int $order_id): self
    {
        $this->order_id = $order_id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: DeliveryMan::class, inversedBy: 'deliverys')]
    #[ORM\JoinColumn(name: 'delivery_man_id', referencedColumnName: 'delivery_man_id')]
    private ?DeliveryMan $deliveryMan = null;

    public function getDeliveryMan(): ?DeliveryMan
    {
        return $this->deliveryMan;
    }

    public function setDeliveryMan(?DeliveryMan $deliveryMan): self
    {
        $this->deliveryMan = $deliveryMan;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Delivery address is required.')]
    #[Assert\Length(min: 5, max: 500, minMessage: 'Delivery address must be at least {{ limit }} characters long.', maxMessage: 'Delivery address cannot be longer than {{ limit }} characters.')]
    private ?string $delivery_address = null;

    public function getDelivery_address(): ?string
    {
        return $this->delivery_address;
    }

    public function setDelivery_address(string $delivery_address): self
    {
        $this->delivery_address = $delivery_address;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Recipient name is required.')]
    #[Assert\Length(max: 100, maxMessage: 'Recipient name cannot be longer than {{ limit }} characters.')]
    private ?string $recipient_name = null;

    public function getRecipient_name(): ?string
    {
        return $this->recipient_name;
    }

    public function setRecipient_name(?string $recipient_name): self
    {
        $this->recipient_name = $recipient_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Recipient phone number is required.')]
    #[Assert\Regex(pattern: '/^[\+]?[0-9\-\(\)\s]+$/', message: 'Please enter a valid phone number.')]
    #[Assert\Length(max: 20, maxMessage: 'Phone number cannot be longer than {{ limit }} characters.')]
    private ?string $recipient_phone = null;

    public function getRecipient_phone(): ?string
    {
        return $this->recipient_phone;
    }

    public function setRecipient_phone(?string $recipient_phone): self
    {
        $this->recipient_phone = $recipient_phone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Pickup location is required.')]
    #[Assert\Length(max: 500, maxMessage: 'Pickup location cannot be longer than {{ limit }} characters.')]
    private ?string $pickup_location = null;

    public function getPickup_location(): ?string
    {
        return $this->pickup_location;
    }

    public function setPickup_location(?string $pickup_location): self
    {
        $this->pickup_location = $pickup_location;
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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scheduled_date = null;

    public function getScheduled_date(): ?\DateTimeInterface
    {
        return $this->scheduled_date;
    }

    public function setScheduled_date(?\DateTimeInterface $scheduled_date): self
    {
        $this->scheduled_date = $scheduled_date;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $actual_delivery_date = null;

    public function getActual_delivery_date(): ?\DateTimeInterface
    {
        return $this->actual_delivery_date;
    }

    public function setActual_delivery_date(?\DateTimeInterface $actual_delivery_date): self
    {
        $this->actual_delivery_date = $actual_delivery_date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $estimated_time = null;

    public function getEstimated_time(): ?int
    {
        return $this->estimated_time;
    }

    public function setEstimated_time(?int $estimated_time): self
    {
        $this->estimated_time = $estimated_time;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $current_latitude = null;

    public function getCurrent_latitude(): ?string
    {
        return $this->current_latitude;
    }

    public function setCurrent_latitude(?string $current_latitude): self
    {
        $this->current_latitude = $current_latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $current_longitude = null;

    public function getCurrent_longitude(): ?string
    {
        return $this->current_longitude;
    }

    public function setCurrent_longitude(?string $current_longitude): self
    {
        $this->current_longitude = $current_longitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $driver_latitude = null;

    public function getDriver_latitude(): ?string
    {
        return $this->driver_latitude;
    }

    public function setDriver_latitude(?string $driver_latitude): self
    {
        $this->driver_latitude = $driver_latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $driver_longitude = null;

    public function getDriver_longitude(): ?string
    {
        return $this->driver_longitude;
    }

    public function setDriver_longitude(?string $driver_longitude): self
    {
        $this->driver_longitude = $driver_longitude;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Delivery notes cannot be longer than {{ limit }} characters.')]
    private ?string $delivery_notes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $candidate_delivery_men = null; // JSON array of delivery_man_id candidates

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $candidate_index = null;

    public function getDelivery_notes(): ?string
    {
        return $this->delivery_notes;
    }

    public function getCandidateDeliveryMen(): ?array
    {
        if (!$this->candidate_delivery_men) return null;
        $data = json_decode($this->candidate_delivery_men, true);
        return is_array($data) ? $data : null;
    }

    public function setCandidateDeliveryMen(?array $ids): self
    {
        $this->candidate_delivery_men = $ids ? json_encode(array_values($ids)) : null;
        return $this;
    }

    public function getCandidateIndex(): ?int
    {
        return $this->candidate_index;
    }

    public function setCandidateIndex(?int $i): self
    {
        $this->candidate_index = $i;
        return $this;
    }

    public function setDelivery_notes(?string $delivery_notes): self
    {
        $this->delivery_notes = $delivery_notes;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cart_items = null;

    public function getCart_items(): ?string
    {
        return $this->cart_items;
    }

    public function setCart_items(?string $cart_items): self
    {
        $this->cart_items = $cart_items;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Order total must be zero or positive.')]
    #[Assert\LessThanOrEqual(value: 99999999.99, message: 'Order total cannot exceed {{ compared_value }}.')]
    private ?string $order_total = null;

    public function getOrder_total(): ?string
    {
        return $this->order_total;
    }

    public function setOrder_total(?string $order_total): self
    {
        $this->order_total = $order_total;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Rating must be between {{ min }} and {{ max }}.')]
    private ?int $rating = null;

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Rating must be between {{ min }} and {{ max }}.')]
    private ?int $restaurant_rating = null;

    public function getRestaurant_rating(): ?int
    {
        return $this->restaurant_rating;
    }

    public function setRestaurant_rating(?int $restaurant_rating): self
    {
        $this->restaurant_rating = $restaurant_rating;
        return $this;
    }

    public function getRestaurantRating(): ?int
    {
        return $this->restaurant_rating;
    }

    public function setRestaurantRating(?int $restaurant_rating): self
    {
        return $this->setRestaurant_rating($restaurant_rating);
    }

    #[ORM\Column(type: 'string', nullable: true, unique: true)]
    #[Assert\Regex(pattern: '/^[0-9]{1,6}[A-Z]{2}[0-9]{1,3}$/', message: 'License plate must match Tunisian format (e.g., 123456AB789).')]
    #[Assert\Length(max: 20, maxMessage: 'License plate cannot be longer than {{ limit }} characters.')]
    private ?string $license_plate = null;

    public function getLicense_plate(): ?string
    {
        return $this->license_plate;
    }

    public function setLicense_plate(?string $license_plate): self
    {
        $this->license_plate = $license_plate;
        return $this;
    }

    public function getLicensePlate(): ?string
    {
        return $this->getLicense_plate();
    }

    public function setLicensePlate(?string $license_plate): self
    {
        return $this->setLicense_plate($license_plate);
    }

    #[ORM\ManyToOne(targetEntity: FleetCar::class)]
    #[ORM\JoinColumn(name: 'fleet_car_id', referencedColumnName: 'car_id', nullable: true)]
    private ?FleetCar $fleetCar = null;

    public function getFleetCar(): ?FleetCar
    {
        return $this->fleetCar;
    }

    public function setFleetCar(?FleetCar $fleetCar): self
    {
        $this->fleetCar = $fleetCar;
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

    // Symfony PropertyAccessor camelCase aliases for snake_case properties
    public function getId(): ?int { return $this->getDelivery_id(); }
    public function setId(int $id): self { return $this->setDelivery_id($id); }
    
    public function getDeliveryId(): ?int { return $this->getDelivery_id(); }
    public function setDeliveryId(int $id): self { return $this->setDelivery_id($id); }
    
    public function getOrderId(): ?int { return $this->getOrder_id(); }
    public function setOrderId(int $id): self { return $this->setOrder_id($id); }
    
    public function getDeliveryAddress(): ?string { return $this->getDelivery_address(); }
    public function setDeliveryAddress(string $addr): self { return $this->setDelivery_address($addr); }
    
    public function getRecipientName(): ?string { return $this->getRecipient_name(); }
    public function setRecipientName(?string $name): self { return $this->setRecipient_name($name); }
    
    public function getRecipientPhone(): ?string { return $this->getRecipient_phone(); }
    public function setRecipientPhone(?string $phone): self { return $this->setRecipient_phone($phone); }
    
    public function getPickupLocation(): ?string { return $this->getPickup_location(); }
    public function setPickupLocation(?string $loc): self { return $this->setPickup_location($loc); }
    
    public function getScheduledDate(): ?\DateTimeInterface { return $this->getScheduled_date(); }
    public function setScheduledDate(?\DateTimeInterface $date): self { return $this->setScheduled_date($date); }
    
    public function getActualDeliveryDate(): ?\DateTimeInterface { return $this->getActual_delivery_date(); }
    public function setActualDeliveryDate(?\DateTimeInterface $date): self { return $this->setActual_delivery_date($date); }
    
    public function getEstimatedTime(): ?int { return $this->getEstimated_time(); }
    public function setEstimatedTime(?int $time): self { return $this->setEstimated_time($time); }
    
    public function getCurrentLatitude(): ?string { return $this->getCurrent_latitude(); }
    public function setCurrentLatitude(?string $lat): self { return $this->setCurrent_latitude($lat); }
    
    public function getCurrentLongitude(): ?string { return $this->getCurrent_longitude(); }
    public function setCurrentLongitude(?string $lon): self { return $this->setCurrent_longitude($lon); }

    public function getDriverLatitude(): ?string { return $this->getDriver_latitude(); }
    public function setDriverLatitude(?string $lat): self { return $this->setDriver_latitude($lat); }

    public function getDriverLongitude(): ?string { return $this->getDriver_longitude(); }
    public function setDriverLongitude(?string $lon): self { return $this->setDriver_longitude($lon); }
    
    public function getDeliveryNotes(): ?string { return $this->getDelivery_notes(); }
    public function setDeliveryNotes(?string $notes): self { return $this->setDelivery_notes($notes); }

    public function getCartItems(): ?string { return $this->getCart_items(); }
    public function setCartItems(?string $items): self { return $this->setCart_items($items); }

    public function getOrderTotal(): ?string { return $this->getOrder_total(); }
    public function setOrderTotal(?string $total): self { return $this->setOrder_total($total); }
    
    public function getCreatedAt(): ?\DateTimeInterface { return $this->getCreated_at(); }
    public function setCreatedAt(\DateTimeInterface $at): self { return $this->setCreated_at($at); }
    
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->getUpdated_at(); }
    public function setUpdatedAt(\DateTimeInterface $at): self { return $this->setUpdated_at($at); }
}
