<?php

namespace App\Entity;

use App\Repository\PermissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_CODE', fields: ['code'])]
#[UniqueEntity(fields: ['code'], message: 'Une permission avec ce code existe déjà')]
#[ORM\HasLifecycleCallbacks]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le code de la permission ne peut pas être vide')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Le code doit faire au moins {{ limit }} caractères', maxMessage: 'Le code ne peut pas dépasser {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^[A-Z_]+$/', message: 'Le code ne peut contenir que des lettres majuscules et des underscores')]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la permission ne peut pas être vide')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit faire au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères')]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La catégorie ne peut pas être vide')]
    #[Assert\Choice(
        choices: ['USER_MANAGEMENT', 'LOAN_MANAGEMENT', 'DOCUMENT_MANAGEMENT', 'SYSTEM_ADMINISTRATION', 'REPORTING', 'FINANCIAL', 'AUDIT'],
        message: 'La catégorie doit être une des valeurs autorisées'
    )]
    private ?string $category = null;

    #[ORM\Column]
    private bool $isSystemPermission = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $priority = 0;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, mappedBy: 'permissions')]
    private Collection $roles;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    // Multi-language support for name and description
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $translations = [];

    // Resource and action for fine-grained control
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $resource = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['CREATE', 'READ', 'UPDATE', 'DELETE', 'LIST', 'EXPORT', 'IMPORT', 'APPROVE', 'REJECT'],
        message: 'L\'action doit être une des valeurs autorisées'
    )]
    private ?string $action = null;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function isSystemPermission(): bool
    {
        return $this->isSystemPermission;
    }

    public function setIsSystemPermission(bool $isSystemPermission): static
    {
        $this->isSystemPermission = $isSystemPermission;
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

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(?string $resource): static
    {
        $this->resource = $resource;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = strtoupper($action);
        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addPermission($this);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        if ($this->roles->removeElement($role)) {
            $role->removePermission($this);
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

    // Utility methods for permission checking
    public function matches(string $resource, string $action): bool
    {
        return ($this->resource === $resource || $this->resource === '*') &&
               ($this->action === strtoupper($action) || $this->action === '*');
    }

    public function getFullPermissionCode(): string
    {
        if ($this->resource && $this->action) {
            return $this->resource . '_' . $this->action;
        }
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->name ?? $this->code ?? 'Permission #' . $this->id;
    }
}