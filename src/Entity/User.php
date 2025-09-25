<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 20)]
    private string $clientType = 'individual'; // 'individual' or 'business'

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyIncome = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyCharges = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $employmentStatus = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $employer = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $employmentStartDate = null;

    // Business-specific fields
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $businessName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $businessRegistration = null;

    #[ORM\Column(nullable: true)]
    private ?int $businessYears = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $annualRevenue = null;

    // Account verification
    #[ORM\Column]
    private bool $isAccountVerified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profileImage = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(choices: ['fr', 'en', 'de', 'es'], message: 'La langue choisie n\'est pas valide')]
    private ?string $preferredLanguage = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $notificationPreferences = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $privacySettings = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $displayPreferences = [];

    #[ORM\Column]
    private bool $isTwoFactorEnabled = false;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    private Collection $userRoles;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->notificationPreferences = [];
        $this->privacySettings = [];
        $this->displayPreferences = [];
        $this->preferredLanguage = 'fr';
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

    public function getMonthlyIncome(): ?string
    {
        return $this->monthlyIncome;
    }

    public function setMonthlyIncome(?float $monthlyIncome): static
    {
        $this->monthlyIncome = $monthlyIncome ? number_format($monthlyIncome, 2, '.', '') : null;
        return $this;
    }

    public function getMonthlyIncomeFloat(): ?float
    {
        return $this->monthlyIncome ? (float) $this->monthlyIncome : null;
    }

    public function getMonthlyCharges(): ?string
    {
        return $this->monthlyCharges;
    }

    public function setMonthlyCharges(?float $monthlyCharges): static
    {
        $this->monthlyCharges = $monthlyCharges ? number_format($monthlyCharges, 2, '.', '') : null;
        return $this;
    }

    public function getMonthlyChargesFloat(): ?float
    {
        return $this->monthlyCharges ? (float) $this->monthlyCharges : null;
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

    public function getAnnualRevenue(): ?string
    {
        return $this->annualRevenue;
    }

    public function setAnnualRevenue(?float $annualRevenue): static
    {
        $this->annualRevenue = $annualRevenue ? number_format($annualRevenue, 2, '.', '') : null;
        return $this;
    }

    public function getAnnualRevenueFloat(): ?float
    {
        return $this->annualRevenue ? (float) $this->annualRevenue : null;
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
        $income = $this->getMonthlyIncomeFloat();
        $charges = $this->getMonthlyChargesFloat();
        
        if ($income === null || $charges === null) {
            return null;
        }
        
        return max(0.0, $income - $charges);
    }

    public function getDebtRatio(): ?float
    {
        $income = $this->getMonthlyIncomeFloat();
        if ($income === null || $income <= 0) {
            return null;
        }
        
        $charges = $this->getMonthlyChargesFloat() ?? 0.0;
        return ($charges / $income) * 100;
    }

    // Enhanced Role Management Methods

    /**
     * @return Collection<int, Role>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function addUserRole(Role $userRole): static
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles->add($userRole);
        }

        return $this;
    }

    public function removeUserRole(Role $userRole): static
    {
        $this->userRoles->removeElement($userRole);

        return $this;
    }

    public function hasUserRole(Role $role): bool
    {
        return $this->userRoles->contains($role);
    }

    public function hasUserRoleByCode(string $roleCode): bool
    {
        foreach ($this->userRoles as $role) {
            if ($role->getCode() === strtoupper($roleCode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has specific permission through their roles
     */
    public function hasPermission(string $permissionCode): bool
    {
        foreach ($this->userRoles as $role) {
            if ($role->hasPermissionByCode($permissionCode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user can perform action on resource
     */
    public function canPerformAction(string $resource, string $action): bool
    {
        foreach ($this->userRoles as $role) {
            foreach ($role->getPermissions() as $permission) {
                if ($permission->matches($resource, $action)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get all permissions from user's roles
     *
     * @return Permission[]
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        foreach ($this->userRoles as $role) {
            foreach ($role->getPermissions() as $permission) {
                $permissions[$permission->getCode()] = $permission;
            }
        }
        return array_values($permissions);
    }

    /**
     * Override getRoles to include Role entities
     */
    public function getRoles(): array
    {
        $roles = $this->roles; // Keep existing string-based roles for compatibility
        
        // Add roles from Role entities
        foreach ($this->userRoles as $userRole) {
            if ($userRole->isActive()) {
                $roles[] = $userRole->getCode();
            }
        }

        // Guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Get highest priority role
     */
    public function getHighestPriorityRole(): ?Role
    {
        $highestRole = null;
        $highestPriority = -1;

        foreach ($this->userRoles as $role) {
            if ($role->isActive() && $role->getPriority() > $highestPriority) {
                $highestPriority = $role->getPriority();
                $highestRole = $role;
            }
        }

        return $highestRole;
    }

    /**
     * Get user's active roles sorted by priority
     *
     * @return Role[]
     */
    public function getActiveRoles(): array
    {
        $activeRoles = [];
        foreach ($this->userRoles as $role) {
            if ($role->isActive()) {
                $activeRoles[] = $role;
            }
        }

        // Sort by priority (highest first)
        usort($activeRoles, fn($a, $b) => $b->getPriority() - $a->getPriority());

        return $activeRoles;
    }

    /**
     * Check if user is an administrator (has any admin role)
     */
    public function isAdministrator(): bool
    {
        return $this->hasUserRoleByCode('ROLE_ADMIN') || 
               $this->hasUserRoleByCode('ROLE_SUPER_ADMIN') ||
               $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Check if user can manage loans
     */
    public function canManageLoans(): bool
    {
        return $this->hasPermission('LOAN_MANAGEMENT_CREATE') || 
               $this->hasPermission('LOAN_MANAGEMENT_UPDATE') ||
               $this->hasUserRoleByCode('ROLE_LOAN_OFFICER');
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermission('USER_MANAGEMENT_CREATE') || 
               $this->hasPermission('USER_MANAGEMENT_UPDATE') ||
               $this->hasUserRoleByCode('ROLE_USER_MANAGER');
    }

    // Profile Management Methods

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;
        return $this;
    }

    public function getProfileImageUrl(): string
    {
        return $this->profileImage 
            ? '/uploads/profile_images/' . $this->profileImage
            : '/images/default-avatar.png';
    }

    public function hasProfileImage(): bool
    {
        return !empty($this->profileImage);
    }

    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage ?? 'fr';
    }

    public function setPreferredLanguage(?string $preferredLanguage): static
    {
        $this->preferredLanguage = $preferredLanguage;
        return $this;
    }

    public function getNotificationPreferences(): ?array
    {
        return $this->notificationPreferences ?? [
            'email_notifications' => true,
            'sms_notifications' => false,
            'loan_status_updates' => true,
            'marketing_communications' => false,
            'security_alerts' => true,
        ];
    }

    public function setNotificationPreferences(?array $notificationPreferences): static
    {
        $this->notificationPreferences = $notificationPreferences;
        return $this;
    }

    public function isNotificationEnabled(string $type): bool
    {
        $preferences = $this->getNotificationPreferences();
        return $preferences[$type] ?? false;
    }

    public function getPrivacySettings(): ?array
    {
        return $this->privacySettings ?? [
            'profile_visibility' => 'private',
            'show_email' => false,
            'show_phone' => false,
            'show_employment_details' => false,
            'data_processing_consent' => true,
        ];
    }

    public function setPrivacySettings(?array $privacySettings): static
    {
        $this->privacySettings = $privacySettings;
        return $this;
    }

    public function getPrivacySetting(string $key): mixed
    {
        $settings = $this->getPrivacySettings();
        return $settings[$key] ?? null;
    }

    public function getDisplayPreferences(): ?array
    {
        return $this->displayPreferences ?? [
            'theme' => 'light',
            'items_per_page' => 10,
            'show_tooltips' => true,
            'compact_view' => false,
        ];
    }

    public function setDisplayPreferences(?array $displayPreferences): static
    {
        $this->displayPreferences = $displayPreferences;
        return $this;
    }

    public function getDisplayPreference(string $key): mixed
    {
        $preferences = $this->getDisplayPreferences();
        return $preferences[$key] ?? null;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->isTwoFactorEnabled;
    }

    public function setIsTwoFactorEnabled(bool $isTwoFactorEnabled): static
    {
        $this->isTwoFactorEnabled = $isTwoFactorEnabled;
        return $this;
    }

    /**
     * Get user's initials for avatar placeholder
     */
    public function getInitials(): string
    {
        $firstName = trim($this->firstName ?? '');
        $lastName = trim($this->lastName ?? '');
        
        $firstInitial = !empty($firstName) ? mb_strtoupper(mb_substr($firstName, 0, 1)) : '';
        $lastInitial = !empty($lastName) ? mb_strtoupper(mb_substr($lastName, 0, 1)) : '';
        
        return $firstInitial . $lastInitial;
    }

    /**
     * Check if profile is complete
     */
    public function isProfileComplete(): bool
    {
        $requiredFields = [
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->phone,
        ];

        return !in_array(null, $requiredFields, true) && !in_array('', $requiredFields, true);
    }

    /**
     * Get profile completion percentage
     */
    public function getProfileCompletion(): int
    {
        $fields = [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'monthlyIncome' => $this->monthlyIncome,
            'employmentStatus' => $this->employmentStatus,
            'profileImage' => $this->profileImage,
        ];

        $completedFields = array_filter($fields, fn($value) => !empty($value));
        return round((count($completedFields) / count($fields)) * 100);
    }

    /**
     * Get user's display name
     */
    public function getDisplayName(): string
    {
        return $this->getFullName() ?: $this->email;
    }

    /**
     * Check if user has completed onboarding
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->isProfileComplete() && $this->isAccountVerified();
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
