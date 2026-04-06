<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\SustainabilityMetricRepository;

#[ORM\Entity(repositoryClass: SustainabilityMetricRepository::class)]
#[ORM\Table(name: 'sustainability_metrics')]
class SustainabilityMetric
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $metric_id = null;

    public function getMetric_id(): ?int
    {
        return $this->metric_id;
    }

    public function setMetric_id(int $metric_id): self
    {
        $this->metric_id = $metric_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $donation_event_id = null;

    public function getDonation_event_id(): ?int
    {
        return $this->donation_event_id;
    }

    public function setDonation_event_id(int $donation_event_id): self
    {
        $this->donation_event_id = $donation_event_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $total_quantity = null;

    public function getTotal_quantity(): ?int
    {
        return $this->total_quantity;
    }

    public function setTotal_quantity(int $total_quantity): self
    {
        $this->total_quantity = $total_quantity;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $meals_provided = null;

    public function getMeals_provided(): ?int
    {
        return $this->meals_provided;
    }

    public function setMeals_provided(int $meals_provided): self
    {
        $this->meals_provided = $meals_provided;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $co2_saved_kg = null;

    public function getCo2_saved_kg(): ?float
    {
        return $this->co2_saved_kg;
    }

    public function setCo2_saved_kg(float $co2_saved_kg): self
    {
        $this->co2_saved_kg = $co2_saved_kg;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $cost_saved = null;

    public function getCost_saved(): ?float
    {
        return $this->cost_saved;
    }

    public function setCost_saved(?float $cost_saved): self
    {
        $this->cost_saved = $cost_saved;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $calculated_at = null;

    public function getCalculated_at(): ?\DateTimeInterface
    {
        return $this->calculated_at;
    }

    public function setCalculated_at(\DateTimeInterface $calculated_at): self
    {
        $this->calculated_at = $calculated_at;
        return $this;
    }

}
