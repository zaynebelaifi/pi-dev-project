<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DishIngredientRepository;

#[ORM\Entity(repositoryClass: DishIngredientRepository::class)]
#[ORM\Table(name: 'dish_ingredient')]
class DishIngredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $dish_id = null;

    public function getDish_id(): ?int
    {
        return $this->dish_id;
    }

    public function setDish_id(int $dish_id): self
    {
        $this->dish_id = $dish_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $ingredient_id = null;

    public function getIngredient_id(): ?int
    {
        return $this->ingredient_id;
    }

    public function setIngredient_id(int $ingredient_id): self
    {
        $this->ingredient_id = $ingredient_id;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $quantity_required = null;

    public function getQuantity_required(): ?float
    {
        return $this->quantity_required;
    }

    public function setQuantity_required(float $quantity_required): self
    {
        $this->quantity_required = $quantity_required;
        return $this;
    }

}
