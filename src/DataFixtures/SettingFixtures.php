<?php

namespace App\DataFixtures;

use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;

class SettingFixtures extends Fixture
{
    // Constantes pour les clés de paramètres (évite les erreurs de frappe)
    public const SITE_NAME = 'site_name';
    public const SITE_DESCRIPTION = 'site_description';
    public const CONTACT_EMAIL = 'contact_email';
    public const CONTACT_PHONE = 'contact_phone';
    public const MAINTENANCE_MODE = 'maintenance_mode';
    public const MAX_LOAN_AMOUNT = 'max_loan_amount';
    public const MIN_LOAN_AMOUNT = 'min_loan_amount';

    // Constantes pour les types de paramètres
    private const TYPE_STRING = 'string';
    private const TYPE_TEXT = 'text';
    private const TYPE_EMAIL = 'email';
    private const TYPE_BOOLEAN = 'boolean';
    private const TYPE_INTEGER = 'integer';

    // Constantes pour les catégories
    private const CATEGORY_GENERAL = 'general';
    private const CATEGORY_CONTACT = 'contact';
    private const CATEGORY_SYSTEM = 'system';
    private const CATEGORY_LOAN = 'loan';

    public function __construct(
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function load(ObjectManager $manager): void
    {
        $this->logger?->info('Loading settings fixtures...');

        $now = new \DateTimeImmutable();
        $settings = $this->getDefaultSettings();

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($settings as $settingData) {
            try {
                // Vérifier si le paramètre existe déjà (évite les doublons)
                $existingSetting = $manager->getRepository(Setting::class)
                    ->findOneBy(['settingKey' => $settingData['key']]);

                if ($existingSetting) {
                    $this->logger?->info('Setting already exists, skipping: ' . $settingData['key']);
                    $skippedCount++;
                    continue;
                }

                $setting = $this->createSettingFromData($settingData, $now);
                $manager->persist($setting);
                $createdCount++;

            } catch (\Exception $e) {
                $this->logger?->error('Error creating setting: ' . $settingData['key'], [
                    'exception' => $e->getMessage()
                ]);
                continue;
            }
        }

        try {
            $manager->flush();
            $this->logger?->info("Settings fixtures loaded successfully", [
                'created' => $createdCount,
                'skipped' => $skippedCount
            ]);
        } catch (\Exception $e) {
            $this->logger?->error('Error flushing settings fixtures', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crée une entité Setting à partir des données
     */
    private function createSettingFromData(array $data, \DateTimeImmutable $now): Setting
    {
        $setting = new Setting();
        
        // Utiliser les setters avec validation
        $setting->setSettingKey($data['key']);
        $setting->setSettingValue($data['value']);
        $setting->setSettingType($data['type']);
        $setting->setSettingName($data['name']);
        $setting->setDescription($data['description']);
        $setting->setCategory($data['category']);
        $setting->setIsPublic($data['public']);
        $setting->setIsRequired($data['required']);
        $setting->setDefaultValue($data['default']);
        $setting->setSortOrder($data['sort']);
        $setting->setCreatedAt($now);
        $setting->setUpdatedAt($now);

        return $setting;
    }

    /**
     * Retourne la configuration par défaut des paramètres
     */
    private function getDefaultSettings(): array
    {
        return [
            [
                'key' => self::SITE_NAME,
                'value' => 'EdgeLoan',
                'type' => self::TYPE_STRING,
                'name' => 'Nom du site',
                'description' => 'Le nom principal du site web affiché dans les templates',
                'category' => self::CATEGORY_GENERAL,
                'public' => true,
                'required' => true,
                'default' => 'EdgeLoan',
                'sort' => 1
            ],
            [
                'key' => self::SITE_DESCRIPTION,
                'value' => 'Solution de prêts en ligne moderne et sécurisée',
                'type' => self::TYPE_TEXT,
                'name' => 'Description du site',
                'description' => 'Description utilisée pour le SEO et les réseaux sociaux',
                'category' => self::CATEGORY_GENERAL,
                'public' => true,
                'required' => true,
                'default' => '',
                'sort' => 2
            ],
            [
                'key' => self::CONTACT_EMAIL,
                'value' => $this->getDefaultContactEmail(),
                'type' => self::TYPE_EMAIL,
                'name' => 'Email de contact',
                'description' => 'Adresse email principale de contact publique',
                'category' => self::CATEGORY_CONTACT,
                'public' => true,
                'required' => true,
                'default' => '',
                'sort' => 1
            ],
            [
                'key' => self::CONTACT_PHONE,
                'value' => $this->getDefaultContactPhone(),
                'type' => self::TYPE_STRING,
                'name' => 'Téléphone de contact',
                'description' => 'Numéro de téléphone principal affiché publiquement',
                'category' => self::CATEGORY_CONTACT,
                'public' => true,
                'required' => true,
                'default' => '',
                'sort' => 2
            ],
            [
                'key' => self::MAINTENANCE_MODE,
                'value' => 'false',
                'type' => self::TYPE_BOOLEAN,
                'name' => 'Mode maintenance',
                'description' => 'Active/désactive le mode maintenance du site',
                'category' => self::CATEGORY_SYSTEM,
                'public' => false,
                'required' => false,
                'default' => 'false',
                'sort' => 1
            ],
            [
                'key' => self::MAX_LOAN_AMOUNT,
                'value' => '100000',
                'type' => self::TYPE_INTEGER,
                'name' => 'Montant maximum de prêt',
                'description' => 'Montant maximum autorisé pour un prêt (en euros)',
                'category' => self::CATEGORY_LOAN,
                'public' => true,
                'required' => true,
                'default' => '100000',
                'sort' => 1
            ],
            [
                'key' => self::MIN_LOAN_AMOUNT,
                'value' => '1000',
                'type' => self::TYPE_INTEGER,
                'name' => 'Montant minimum de prêt',
                'description' => 'Montant minimum autorisé pour un prêt (en euros)',
                'category' => self::CATEGORY_LOAN,
                'public' => true,
                'required' => true,
                'default' => '1000',
                'sort' => 2
            ],
        ];
    }

    /**
     * Génère un email de contact approprié selon l'environnement
     */
    private function getDefaultContactEmail(): string
    {
        $environment = $_ENV['APP_ENV'] ?? 'dev';
        
        return match($environment) {
            'prod' => 'contact@edgeloan.com',
            'staging' => 'contact@staging.edgeloan.com',
            default => 'contact@dev.edgeloan.local'
        };
    }

    /**
     * Génère un numéro de téléphone approprié selon l'environnement
     */
    private function getDefaultContactPhone(): string
    {
        $environment = $_ENV['APP_ENV'] ?? 'dev';
        
        return match($environment) {
            'prod' => '+33 1 23 45 67 89',
            default => '+33 1 00 00 00 00' // Numéro fictif pour dev/staging
        };
    }
}