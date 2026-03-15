<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Username is required')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Username must be at least {{ limit }} characters', maxMessage: 'Username cannot exceed {{ limit }} characters')]
    private ?string $username = null;

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^9\d{9}$/',
        message: 'Phone number must start with 9 and be exactly 10 digits (e.g., 9123456789)'
    )]
    #[Assert\Length(
        min: 10,
        max: 10,
        exactMessage: 'Phone number must be exactly 10 digits'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['admin', 'manager', 'mechanic', 'staff'], message: 'Please select a valid role')]
    private ?string $role = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'active';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\OneToMany(mappedBy: 'assignedMechanic', targetEntity: Service::class)]
    private Collection $assignedServices;

    public function __construct()
    {
        $this->assignedServices = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->setRole('staff'); // default self-registered users to staff
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) ($this->username ?? $this->email ?? '');
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER (no automatic ROLE_STAFF for mechanics)
        if (empty($roles)) {
            $roles[] = 'ROLE_STAFF';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->resolveRole();
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        
        // Set roles based on role
        switch ($role) {
            case 'admin':
                $this->roles = ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_MECHANIC', 'ROLE_STAFF'];
                break;
            case 'manager':
                $this->roles = ['ROLE_MANAGER', 'ROLE_MECHANIC', 'ROLE_STAFF'];
                break;
            case 'mechanic':
                $this->roles = ['ROLE_MECHANIC', 'ROLE_STAFF'];
                break;
            case 'staff':
                $this->roles = ['ROLE_STAFF'];
                break;
        }
        
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

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getAssignedServices(): Collection
    {
        return $this->assignedServices;
    }

    public function addAssignedService(Service $assignedService): static
    {
        if (!$this->assignedServices->contains($assignedService)) {
            $this->assignedServices->add($assignedService);
            $assignedService->setAssignedMechanic($this);
        }

        return $this;
    }

    public function removeAssignedService(Service $assignedService): static
    {
        if ($this->assignedServices->removeElement($assignedService)) {
            // set the owning side to null (unless already changed)
            if ($assignedService->getAssignedMechanic() === $this) {
                $assignedService->setAssignedMechanic(null);
            }
        }

        return $this;
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    public function isManager(): bool
    {
        return in_array('ROLE_MANAGER', $this->roles);
    }

    public function isMechanic(): bool
    {
        return in_array('ROLE_MECHANIC', $this->roles);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the user is enabled (not suspended or inactive)
     * Required by Symfony UserInterface - prevents suspended/inactive users from logging in
     */
    public function isEnabled(): bool
    {
        return !in_array($this->status, ['suspended', 'inactive']);
    }

    public function getRoleDisplayName(): string
    {
        return match($this->resolveRole()) {
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'mechanic' => 'Mechanic',
            'staff' => 'Staff',
            default => 'Unknown'
        };
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            default => 'Unknown'
        };
    }

    /**
     * Determine the primary role, falling back to Symfony roles when the
     * persisted string role is missing or in an unexpected format.
     */
    private function resolveRole(): string
    {
        if (in_array($this->role, ['admin', 'manager', 'mechanic', 'staff'], true)) {
            return $this->role;
        }

        $roles = $this->roles;

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'admin';
        }
        if (in_array('ROLE_MANAGER', $roles, true)) {
            return 'manager';
        }
        if (in_array('ROLE_MECHANIC', $roles, true)) {
            return 'mechanic';
        }
        if (in_array('ROLE_STAFF', $roles, true)) {
            return 'staff';
        }

        return 'unknown';
    }
}
