<?php

namespace App\Twig;

use App\Service\SettingService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class SettingExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SettingService $settingService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('setting', [$this, 'getSetting']),
            new TwigFunction('settings', [$this, 'getSettings']),
            new TwigFunction('public_settings', [$this, 'getPublicSettings']),
            new TwigFunction('setting_exists', [$this, 'settingExists']),
        ];
    }

    public function getGlobals(): array
    {
        try {
            // Load only public settings as globals for security
            $publicSettings = $this->settingService->getPublicSettings();
            
            return [
                'site_settings' => $publicSettings,
                // Common site settings as direct globals for convenience
                'site_name' => $publicSettings['site_name'] ?? 'EdgeLoan',
                'site_description' => $publicSettings['site_description'] ?? '',
                'site_logo' => $publicSettings['site_logo'] ?? '/assets/images/logo.png',
                'contact_email' => $publicSettings['contact_email'] ?? '',
                'contact_phone' => $publicSettings['contact_phone'] ?? '',
            ];
        } catch (\Exception $e) {
            // In case of database error or during migration
            return [
                'site_settings' => [],
                'site_name' => 'EdgeLoan',
                'site_description' => '',
                'site_logo' => '/assets/images/logo.png',
                'contact_email' => '',
                'contact_phone' => '',
            ];
        }
    }

    /**
     * Get a single setting value
     */
    public function getSetting(string $key, mixed $fallback = null): mixed
    {
        return $this->settingService->get($key, $fallback);
    }

    /**
     * Get all settings (admin only - use with caution)
     */
    public function getSettings(): array
    {
        return $this->settingService->getAllSettings();
    }

    /**
     * Get only public settings (safe for frontend)
     */
    public function getPublicSettings(): array
    {
        return $this->settingService->getPublicSettings();
    }

    /**
     * Check if a setting exists
     */
    public function settingExists(string $key): bool
    {
        $settings = $this->settingService->getAllSettings();
        return array_key_exists($key, $settings);
    }
}