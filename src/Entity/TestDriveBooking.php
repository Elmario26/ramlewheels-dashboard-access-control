<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\TestDriveBookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TestDriveBookingRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['testdrive:read']],
            security: 'is_granted("ROLE_CUSTOMER") or is_granted("ROLE_STAFF")',
        ),
        new Get(
            normalizationContext: ['groups' => ['testdrive:read']],
            security: 'is_granted("ROLE_CUSTOMER") or is_granted("ROLE_STAFF")',
        ),
        new Post(
            normalizationContext: ['groups' => ['testdrive:read']],
            denormalizationContext: ['groups' => ['testdrive:write']],
            security: 'is_granted("ROLE_CUSTOMER")',
        ),
        new Patch(
            normalizationContext: ['groups' => ['testdrive:read']],
            denormalizationContext: ['groups' => ['testdrive:update']],
            security: 'is_granted("ROLE_STAFF")',
        ),
    ],
)]
class TestDriveBooking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['testdrive:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['testdrive:read', 'testdrive:write'])]
    private ?User $customer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['testdrive:read', 'testdrive:write'])]
    private ?Cars $car = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['testdrive:read', 'testdrive:write'])]
    private ?\DateTimeInterface $requestedDateTime = null;

    #[ORM\Column(length: 50)]
    #[Groups(['testdrive:read', 'testdrive:update'])]
    private string $status = 'pending'; // pending, approved, rejected, completed

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['testdrive:read', 'testdrive:write'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['testdrive:read', 'testdrive:update'])]
    private ?string $staffRemarks = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['testdrive:read'])]
    private ?User $approvedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['testdrive:read'])]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['testdrive:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['testdrive:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getCar(): ?Cars
    {
        return $this->car;
    }

    public function setCar(?Cars $car): static
    {
        $this->car = $car;
        return $this;
    }

    public function getRequestedDateTime(): ?\DateTimeInterface
    {
        return $this->requestedDateTime;
    }

    public function setRequestedDateTime(\DateTimeInterface $requestedDateTime): static
    {
        $this->requestedDateTime = $requestedDateTime;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStaffRemarks(): ?string
    {
        return $this->staffRemarks;
    }

    public function setStaffRemarks(?string $staffRemarks): static
    {
        $this->staffRemarks = $staffRemarks;
        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): static
    {
        $this->approvedBy = $approvedBy;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeInterface $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
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

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
