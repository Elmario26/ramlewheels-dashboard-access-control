<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\CarsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CarsRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['car:read']],
            security: 'true',
        ),
        new Get(
            normalizationContext: ['groups' => ['car:read']],
            security: 'true',
        ),
        new Post(
            normalizationContext: ['groups' => ['car:read']],
            denormalizationContext: ['groups' => ['car:write']],
            security: "is_granted('ROLE_STAFF')",
        ),
        new Put(
            normalizationContext: ['groups' => ['car:read']],
            denormalizationContext: ['groups' => ['car:write']],
            security: "is_granted('ROLE_STAFF')",
        ),
        new Patch(
            normalizationContext: ['groups' => ['car:read']],
            denormalizationContext: ['groups' => ['car:write']],
            security: "is_granted('ROLE_STAFF')",
        ),
        new Delete(security: "is_granted('ROLE_STAFF')"),
    ],
)]
class Cars
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['car:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $brand = null;

    #[ORM\Column(length: 255)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $Year = null;

    #[ORM\Column(length: 255)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $Mileage = null;

    #[ORM\Column(length: 255)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $conditions = null;

    #[ORM\Column]
    #[Groups(['car:read', 'car:write'])]
    private ?float $price = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['car:read', 'car:write'])]
    private array $images = [];

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $status = 'available';

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $make = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $color = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $plateNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $engineNumber = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['car:read', 'car:write'])]
    private ?string $damageDescription = null;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Service::class)]
    private Collection $services;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getYear(): ?string
    {
        return $this->Year;
    }

    public function setYear(string $Year): static
    {
        $this->Year = $Year;

        return $this;
    }

    public function getMileage(): ?string
    {
        return $this->Mileage;
    }

    public function setMileage(string $Mileage): static
    {
        $this->Mileage = $Mileage;

        return $this;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function setConditions(string $conditions): static
    {
        $this->conditions = $conditions;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): static
    {
        $this->images = $images;

        return $this;
    }

    public function addImage(string $imagePath): static
    {
        if (!in_array($imagePath, $this->images)) {
            $this->images[] = $imagePath;
        }

        return $this;
    }

    public function removeImage(string $imagePath): static
    {
        $key = array_search($imagePath, $this->images);
        if ($key !== false) {
            unset($this->images[$key]);
            $this->images = array_values($this->images);
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMake(): ?string
    {
        return $this->make;
    }

    public function setMake(?string $make): static
    {
        $this->make = $make;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getPlateNumber(): ?string
    {
        return $this->plateNumber;
    }

    public function setPlateNumber(?string $plateNumber): static
    {
        $this->plateNumber = $plateNumber;

        return $this;
    }

    public function getEngineNumber(): ?string
    {
        return $this->engineNumber;
    }

    public function setEngineNumber(?string $engineNumber): static
    {
        $this->engineNumber = $engineNumber;

        return $this;
    }

    public function getDamageDescription(): ?string
    {
        return $this->damageDescription;
    }

    public function setDamageDescription(?string $damageDescription): static
    {
        $this->damageDescription = $damageDescription;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setVehicle($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getVehicle() === $this) {
                $service->setVehicle(null);
            }
        }

        return $this;
    }

    public function getTotalServiceCost(): float
    {
        $total = 0;
        foreach ($this->services as $service) {
            if ($service->getStatus() === 'completed') {
                $total += (float)$service->getCost();
            }
        }

        return $total;
    }

    public function getServiceCount(): int
    {
        return $this->services->filter(function (Service $service) {
            return $service->getStatus() === 'completed';
        })->count();
    }
}
