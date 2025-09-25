<?php

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SettingService
{
    private const CACHE_KEY = 'app_settings';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get setting value by key
     */
    public function get(string $key, mixed $fallback = null): mixed
    {
        $settings = $this->getAllSettings();
        return $settings[$key] ?? $fallback;
    }

    /**
     * Set setting value by key
     */
    public function set(string $key, mixed $value): bool
    {
        $result = $this->settingRepository->setValueByKey($key, $value);
        if ($result) {
            $this->clearCache();
        }
        return $result;
    }

    /**
     * Get all settings as key-value pairs (cached)
     */
    public function getAllSettings(): array
    {
        try {
            return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
                $item->expiresAfter(self::CACHE_TTL);
                return $this->settingRepository->getAllSettingsAsArray();
            });
        } catch (InvalidArgumentException) {
            return $this->settingRepository->getAllSettingsAsArray();
        }
    }

    /**
     * Get public settings only (for frontend use)
     */
    public function getPublicSettings(): array
    {
        try {
            return $this->cache->get(self::CACHE_KEY . '_public', function (ItemInterface $item): array {
                $item->expiresAfter(self::CACHE_TTL);
                return $this->settingRepository->findPublicSettingsAsArray();
            });
        } catch (InvalidArgumentException) {
            return $this->settingRepository->findPublicSettingsAsArray();
        }
    }

    /**
     * Get settings grouped by category
     */
    public function getSettingsByCategory(): array
    {
        return $this->settingRepository->findByCategory();
    }

    /**
     * Get settings for a specific category
     */
    public function getCategorySettings(string $category): array
    {
        return $this->settingRepository->findByCategoryName($category);
    }

    /**
     * Create or update a setting
     */
    public function createOrUpdate(
        string $key,
        mixed $value,
        string $type = Setting::TYPE_STRING,
        string $name = null,
        string $description = null,
        string $category = 'general',
        bool $isPublic = false,
        bool $isRequired = false,
        mixed $defaultValue = null
    ): Setting {
        $setting = $this->settingRepository->createOrUpdate(
            $key,
            $value,
            $type,
            $name,
            $description,
            $category,
            $isPublic,
            $isRequired,
            $defaultValue
        );
        
        $this->clearCache();
        return $setting;
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(array $settings): int
    {
        $updated = 0;
        foreach ($settings as $key => $value) {
            if ($this->set($key, $value)) {
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * Get missing required settings
     */
    public function getMissingRequiredSettings(): array
    {
        return $this->settingRepository->findMissingRequiredSettings();
    }

    /**
     * Check if all required settings are configured
     */
    public function areRequiredSettingsConfigured(): bool
    {
        return empty($this->getMissingRequiredSettings());
    }

    /**
     * Clear settings cache
     */
    public function clearCache(): void
    {
        try {
            $this->cache->delete(self::CACHE_KEY);
            $this->cache->delete(self::CACHE_KEY . '_public');
        } catch (InvalidArgumentException) {
            // Ignore cache errors
        }
    }

    /**
     * Initialize default settings for the application
     */
    public function initializeDefaultSettings(): void
    {
        $defaultSettings = [
            // Site information
            'site_name' => [
                'value' => 'EdgeLoan',
                'type' => Setting::TYPE_STRING,
                'name' => 'Nom du site',
                'description' => 'Le nom principal du site web',
                'category' => 'general',
                'public' => true,
                'required' => true
            ],
            'site_description' => [
                'value' => 'Solution de prêts en ligne moderne et sécurisée',
                'type' => Setting::TYPE_TEXT,
                'name' => 'Description du site',
                'description' => 'Description utilisée pour le SEO et les réseaux sociaux',
                'category' => 'general',
                'public' => true,
                'required' => true
            ],
            'site_url' => [
                'value' => 'https://edgeloan.com',
                'type' => Setting::TYPE_URL,
                'name' => 'URL du site',
                'description' => 'URL complète du site web',
                'category' => 'general',
                'public' => true,
                'required' => true
            ],
            'site_logo' => [
                'value' => '/assets/images/logo.png',
                'type' => Setting::TYPE_FILE,
                'name' => 'Logo du site',
                'description' => 'Chemin vers le logo principal',
                'category' => 'branding',
                'public' => true,
                'required' => false
            ],
            'site_favicon' => [
                'value' => '/favicon.ico',
                'type' => Setting::TYPE_FILE,
                'name' => 'Favicon',
                'description' => 'Icône du site (favicon)',
                'category' => 'branding',
                'public' => true,
                'required' => false
            ],
            
            // Contact information
            'contact_email' => [
                'value' => 'contact@edgeloan.com',
                'type' => Setting::TYPE_EMAIL,
                'name' => 'Email de contact',
                'description' => 'Adresse email principale de contact',
                'category' => 'contact',
                'public' => true,
                'required' => true
            ],
            'contact_phone' => [
                'value' => '+33 1 23 45 67 89',
                'type' => Setting::TYPE_STRING,
                'name' => 'Téléphone de contact',
                'description' => 'Numéro de téléphone principal',
                'category' => 'contact',
                'public' => true,
                'required' => true
            ],
            'contact_address' => [
                'value' => '123 Rue de la Finance, 75001 Paris, France',
                'type' => Setting::TYPE_TEXT,
                'name' => 'Adresse',
                'description' => 'Adresse postale complète',
                'category' => 'contact',
                'public' => true,
                'required' => true
            ],
            
            // Social media
            'social_facebook' => [
                'value' => 'https://facebook.com/edgeloan',
                'type' => Setting::TYPE_URL,
                'name' => 'Facebook',
                'description' => 'URL de la page Facebook',
                'category' => 'social',
                'public' => true,
                'required' => false
            ],
            'social_twitter' => [
                'value' => 'https://twitter.com/edgeloan',
                'type' => Setting::TYPE_URL,
                'name' => 'Twitter/X',
                'description' => 'URL du profil Twitter/X',
                'category' => 'social',
                'public' => true,
                'required' => false
            ],
            'social_linkedin' => [
                'value' => 'https://linkedin.com/company/edgeloan',
                'type' => Setting::TYPE_URL,
                'name' => 'LinkedIn',
                'description' => 'URL de la page LinkedIn',
                'category' => 'social',
                'public' => true,
                'required' => false
            ],
            
            // Application settings
            'maintenance_mode' => [
                'value' => false,
                'type' => Setting::TYPE_BOOLEAN,
                'name' => 'Mode maintenance',
                'description' => 'Activer/désactiver le mode maintenance',
                'category' => 'system',
                'public' => false,
                'required' => false,
                'default' => false
            ],
            'max_loan_amount' => [
                'value' => 100000,
                'type' => Setting::TYPE_INTEGER,
                'name' => 'Montant maximum de prêt',
                'description' => 'Montant maximum autorisé pour un prêt',
                'category' => 'loan',
                'public' => true,
                'required' => true
            ],
            'min_loan_amount' => [
                'value' => 1000,
                'type' => Setting::TYPE_INTEGER,
                'name' => 'Montant minimum de prêt',
                'description' => 'Montant minimum autorisé pour un prêt',
                'category' => 'loan',
                'public' => true,
                'required' => true
            ],
            
            // Theme settings
            'theme_primary_color' => [
                'value' => '#007bff',
                'type' => Setting::TYPE_COLOR,
                'name' => 'Couleur principale',
                'description' => 'Couleur principale du thème',
                'category' => 'theme',
                'public' => true,
                'required' => false,
                'default' => '#007bff'
            ],
            'theme_secondary_color' => [
                'value' => '#6c757d',
                'type' => Setting::TYPE_COLOR,
                'name' => 'Couleur secondaire',
                'description' => 'Couleur secondaire du thème',
                'category' => 'theme',
                'public' => true,
                'required' => false,
                'default' => '#6c757d'
            ],
        ];

        foreach ($defaultSettings as $key => $config) {
            $this->createOrUpdate(
                $key,
                $config['value'],
                $config['type'],
                $config['name'],
                $config['description'],
                $config['category'],
                $config['public'],
                $config['required'],
                $config['default'] ?? null
            );
        }
    }
}