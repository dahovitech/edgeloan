<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_SETTING_KEY', fields: ['settingKey'])]
#[UniqueEntity(fields: ['settingKey'], message: 'Cette clé de paramètre existe déjà')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'setting')]
class Setting
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_JSON = 'json';
    public const TYPE_TEXT = 'text';
    public const TYPE_URL = 'url';
    public const TYPE_EMAIL = 'email';
    public const TYPE_COLOR = 'color';
    public const TYPE_FILE = 'file';

    public const TYPES = [
        self::TYPE_STRING => 'Texte',
        self::TYPE_INTEGER => 'Nombre entier',
        self::TYPE_BOOLEAN => 'Booléen (Oui/Non)',
        self::TYPE_JSON => 'JSON',
        self::TYPE_TEXT => 'Texte long',
        self::TYPE_URL => 'URL',
        self::TYPE_EMAIL => 'Email',
        self::TYPE_COLOR => 'Couleur',
        self::TYPE_FILE => 'Fichier',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'La clé du paramètre ne peut pas être vide')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'La clé doit faire au moins {{ limit }} caractères', maxMessage: 'La clé ne peut pas dépasser {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]*[a-z0-9]$/', message: 'La clé doit contenir uniquement des lettres minuscules, chiffres et underscores, et ne peut pas commencer ou finir par un underscore')]
    private ?string $settingKey = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $settingValue = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
        self::TYPE_JSON,
        self::TYPE_TEXT,
        self::TYPE_URL,
        self::TYPE_EMAIL,
        self::TYPE_COLOR,
        self::TYPE_FILE
    ], message: 'Type de paramètre invalide')]
    private string $settingType = self::TYPE_STRING;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du paramètre ne peut pas être vide')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le nom doit faire au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $settingName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La catégorie ne peut pas être vide')]
    private string $category = 'general';

    #[ORM\Column]
    private bool $isPublic = false;

    #[ORM\Column]
    private bool $isRequired = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultValue = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $options = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?int $sortOrder = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getSettingKey(): ?string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): static
    {
        $this->settingKey = $settingKey;

        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(?string $settingValue): static
    {
        $this->settingValue = $settingValue;

        return $this;
    }

    public function getSettingType(): string
    {
        return $this->settingType;
    }

    public function setSettingType(string $settingType): static
    {
        $this->settingType = $settingType;

        return $this;
    }

    public function getSettingName(): ?string
    {
        return $this->settingName;
    }

    public function setSettingName(string $settingName): static
    {
        $this->settingName = $settingName;

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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options;

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

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get the typed value based on the setting type
     */
    public function getTypedValue(): mixed
    {
        if ($this->settingValue === null) {
            return $this->getTypedDefaultValue();
        }

        return match ($this->settingType) {
            self::TYPE_INTEGER => (int) $this->settingValue,
            self::TYPE_BOOLEAN => (bool) filter_var($this->settingValue, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => json_decode($this->settingValue, true),
            default => $this->settingValue,
        };
    }

    /**
     * Get the typed default value
     */
    public function getTypedDefaultValue(): mixed
    {
        if ($this->defaultValue === null) {
            return null;
        }

        return match ($this->settingType) {
            self::TYPE_INTEGER => (int) $this->defaultValue,
            self::TYPE_BOOLEAN => (bool) filter_var($this->defaultValue, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => json_decode($this->defaultValue, true),
            default => $this->defaultValue,
        };
    }

    /**
     * Set value from any type (converts to string for storage)
     */
    public function setTypedValue(mixed $value): static
    {
        if ($value === null) {
            $this->settingValue = null;
            return $this;
        }

        $this->settingValue = match ($this->settingType) {
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            self::TYPE_JSON => json_encode($value),
            default => (string) $value,
        };

        return $this;
    }

    /**
     * Get the current effective value (with fallback to default)
     */
    public function getValue(): mixed
    {
        return $this->settingValue !== null ? $this->getTypedValue() : $this->getTypedDefaultValue();
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->settingType] ?? $this->settingType;
    }

    public function __toString(): string
    {
        return $this->settingName ?? $this->settingKey ?? '';
    }
}