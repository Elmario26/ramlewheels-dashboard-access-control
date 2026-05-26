<?php

namespace App\Entity;

use App\Repository\ServiceBookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceBookingRepository::class)]
#[ORM\Table(name: 'service_booking')]
class ServiceBooking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\Column(length: 64)]
    private string $serviceId = '';

    #[ORM\Column(length: 255)]
    private string $serviceName = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $vehicleDescription = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $requestedDateTime = null;

    #[ORM\Column(length: 32)]
    private string $phone = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 50)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $staffRemarks = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $approvedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
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

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): static
    {
        $this->serviceId = $serviceId;

        return $this;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function setServiceName(string $serviceName): static
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    public function getVehicleDescription(): string
    {
        return $this->vehicleDescription;
    }

    public function setVehicleDescription(string $vehicleDescription): static
    {
        $this->vehicleDescription = $vehicleDescription;

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

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
