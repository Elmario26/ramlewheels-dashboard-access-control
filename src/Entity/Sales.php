<?php

namespace App\Entity;

use App\Repository\SalesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SalesRepository::class)]
#[ORM\Table(name: 'sales')]
class Sales
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cars::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cars $vehicle = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'sales')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Customer is required')]
    private ?Customer $customer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Sale price is required')]
    #[Assert\Positive(message: 'Sale price must be positive')]
    private ?string $salePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Down payment must be positive or zero')]
    private ?string $downPayment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Financing amount must be positive or zero')]
    private ?string $financingAmount = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $saleDate = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->saleDate = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->status = 'completed';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Cars
    {
        return $this->vehicle;
    }

    public function setVehicle(?Cars $vehicle): static
    {
        $this->vehicle = $vehicle;
        return $this;
    }


    public function getSalePrice(): ?string
    {
        return $this->salePrice;
    }

    public function setSalePrice(string $salePrice): static
    {
        $this->salePrice = $salePrice;
        return $this;
    }

    public function getDownPayment(): ?string
    {
        return $this->downPayment;
    }

    public function setDownPayment(?string $downPayment): static
    {
        $this->downPayment = $downPayment;
        return $this;
    }

    public function getFinancingAmount(): ?string
    {
        return $this->financingAmount;
    }

    public function setFinancingAmount(?string $financingAmount): static
    {
        $this->financingAmount = $financingAmount;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getSaleDate(): ?\DateTimeInterface
    {
        return $this->saleDate;
    }

    public function setSaleDate(\DateTimeInterface $saleDate): static
    {
        $this->saleDate = $saleDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Helper methods
    public function getProfit(): ?float
    {
        if ($this->vehicle && $this->salePrice) {
            return (float)$this->salePrice - (float)$this->vehicle->getPrice();
        }
        return null;
    }

    public function getProfitMargin(): ?float
    {
        if ($this->vehicle && $this->salePrice) {
            $cost = (float)$this->vehicle->getPrice();
            $sale = (float)$this->salePrice;
            return $cost > 0 ? (($sale - $cost) / $cost) * 100 : 0;
        }
        return null;
    }

    public function isFinanced(): bool
    {
        return !empty($this->financingAmount) && (float)$this->financingAmount > 0;
    }

    public function getRemainingBalance(): ?float
    {
        if ($this->isFinanced() && $this->downPayment) {
            return (float)$this->financingAmount - (float)$this->downPayment;
        }
        return null;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
