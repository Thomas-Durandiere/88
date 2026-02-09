<?php

namespace App\Entity;

use App\Repository\OrderProductsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderProductsRepository::class)]
class OrderProducts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price_unit = null;

    #[ORM\ManyToOne(inversedBy: 'OrderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $OrderRef = null;

    #[ORM\ManyToOne(inversedBy: 'OrderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Products $Products = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPriceUnit(): ?string
    {
        return $this->price_unit;
    }

    public function setPriceUnit(string $price_unit): static
    {
        $this->price_unit = $price_unit;

        return $this;
    }

    public function getOrderRef(): ?Order
    {
        return $this->OrderRef;
    }

    public function setOrderRef(?Order $OrderRef): static
    {
        $this->OrderRef = $OrderRef;

        return $this;
    }

    public function getProducts(): ?Products
    {
        return $this->Products;
    }

    public function setProducts(?Products $Products): static
    {
        $this->Products = $Products;

        return $this;
    }
}
