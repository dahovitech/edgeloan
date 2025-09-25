<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit faire au moins {{ limit }} caractères', maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit faire au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $lastName = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    // Loan-specific fields
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $clientType = 'individual'; // 'individual' or 'business'

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $monthlyIncome = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $monthlyCharges = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $employmentStatus = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $employer = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $employmentStartDate = null;

    // Business-specific fields
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $businessName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $businessRegistration = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $businessYears = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?float $annualRevenue = null;

    // Account verification
    #[ORM\Column(type: 'boolean')]
    private bool $isAccountVerified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

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

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
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
        return trim($this->firstName . ' ' . $this->lastName);
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

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    // Loan-specific getters and setters
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getClientType(): string
    {
        return $this->clientType;
    }

    public function setClientType(string $clientType): static
    {
        $this->clientType = $clientType;
        return $this;
    }

    public function isIndividual(): bool
    {
        return $this->clientType === 'individual';
    }

    public function isBusiness(): bool
    {
        return $this->clientType === 'business';
    }

    public function getMonthlyIncome(): ?float
    {
        return $this->monthlyIncome;
    }

    public function setMonthlyIncome(?float $monthlyIncome): static
    {
        $this->monthlyIncome = $monthlyIncome;
        return $this;
    }

    public function getMonthlyCharges(): ?float
    {
        return $this->monthlyCharges;
    }

    public function setMonthlyCharges(?float $monthlyCharges): static
    {
        $this->monthlyCharges = $monthlyCharges;
        return $this;
    }

    public function getEmploymentStatus(): ?string
    {
        return $this->employmentStatus;
    }

    public function setEmploymentStatus(?string $employmentStatus): static
    {
        $this->employmentStatus = $employmentStatus;
        return $this;
    }

    public function getEmployer(): ?string
    {
        return $this->employer;
    }

    public function setEmployer(?string $employer): static
    {
        $this->employer = $employer;
        return $this;
    }

    public function getEmploymentStartDate(): ?\DateTimeInterface
    {
        return $this->employmentStartDate;
    }

    public function setEmploymentStartDate(?\DateTimeInterface $employmentStartDate): static
    {
        $this->employmentStartDate = $employmentStartDate;
        return $this;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(?string $businessName): static
    {
        $this->businessName = $businessName;
        return $this;
    }

    public function getBusinessRegistration(): ?string
    {
        return $this->businessRegistration;
    }

    public function setBusinessRegistration(?string $businessRegistration): static
    {
        $this->businessRegistration = $businessRegistration;
        return $this;
    }

    public function getBusinessYears(): ?int
    {
        return $this->businessYears;
    }

    public function setBusinessYears(?int $businessYears): static
    {
        $this->businessYears = $businessYears;
        return $this;
    }

    public function getAnnualRevenue(): ?float
    {
        return $this->annualRevenue;
    }

    public function setAnnualRevenue(?float $annualRevenue): static
    {
        $this->annualRevenue = $annualRevenue;
        return $this;
    }

    public function isAccountVerified(): bool
    {
        return $this->isAccountVerified;
    }

    public function setIsAccountVerified(bool $isAccountVerified): static
    {
        $this->isAccountVerified = $isAccountVerified;
        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;
        return $this;
    }

    public function getNetIncome(): ?float
    {
        if ($this->monthlyIncome === null || $this->monthlyCharges === null) {
            return null;
        }
        return max(0, $this->monthlyIncome - $this->monthlyCharges);
    }

    public function getDebtRatio(): ?float
    {
        if ($this->monthlyIncome === null || $this->monthlyIncome <= 0) {
            return null;
        }
        $charges = $this->monthlyCharges ?? 0;
        return ($charges / $this->monthlyIncome) * 100;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
