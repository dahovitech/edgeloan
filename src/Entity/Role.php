<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_CODE', fields: ['code'])]
#[UniqueEntity(fields: ['code'], message: 'Un rôle avec ce code existe déjà')]
#[ORM\HasLifecycleCallbacks]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le code du rôle ne peut pas être vide')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Le code doit faire au moins {{ limit }} caractères', maxMessage: 'Le code ne peut pas dépasser {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^[A-Z_]+$/', message: 'Le code ne peut contenir que des lettres majuscules et des underscores')]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom du rôle ne peut pas être vide')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit faire au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères')]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isSystemRole = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $priority = 0;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\ManyToMany(targetEntity: Permission::class)]
    #[ORM\JoinTable(name: 'role_permissions')]
    private Collection $permissions;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'userRoles')]
    private Collection $users;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    // Multi-language support for name and description
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $translations = [];

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->translations = [];
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper($code);
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isSystemRole(): bool
    {
        return $this->isSystemRole;
    }

    public function setIsSystemRole(bool $isSystemRole): static
    {
        $this->isSystemRole = $isSystemRole;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        $this->permissions->removeElement($permission);

        return $this;
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->permissions->contains($permission);
    }

    public function hasPermissionByCode(string $permissionCode): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission->getCode() === $permissionCode) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addUserRole($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeUserRole($this);
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Multi-language support methods
    public function getTranslations(): ?array
    {
        return $this->translations;
    }

    public function setTranslations(?array $translations): static
    {
        $this->translations = $translations;
        return $this;
    }

    public function getTranslatedName(string $locale = 'fr'): string
    {
        return $this->translations['name'][$locale] ?? $this->name ?? '';
    }

    public function setTranslatedName(string $locale, string $name): static
    {
        if (!isset($this->translations['name'])) {
            $this->translations['name'] = [];
        }
        $this->translations['name'][$locale] = $name;
        return $this;
    }

    public function getTranslatedDescription(string $locale = 'fr'): ?string
    {
        return $this->translations['description'][$locale] ?? $this->description;
    }

    public function setTranslatedDescription(string $locale, ?string $description): static
    {
        if (!isset($this->translations['description'])) {
            $this->translations['description'] = [];
        }
        $this->translations['description'][$locale] = $description;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? $this->code ?? 'Role #' . $this->id;
    }
}