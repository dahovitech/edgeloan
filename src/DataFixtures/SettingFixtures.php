<?php

namespace App\DataFixtures;

use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SettingFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();
        
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'EdgeLoan',
                'type' => 'string',
                'name' => 'Nom du site',
                'description' => 'Le nom principal du site web',
                'category' => 'general',
                'public' => true,
                'required' => true,
                'default' => 'EdgeLoan',
                'sort' => 1
            ],
            [
                'key' => 'site_description',
                'value' => 'Solution de prêts en ligne moderne et sécurisée',
                'type' => 'text',
                'name' => 'Description du site',
                'description' => 'Description utilisée pour le SEO et les réseaux sociaux',
                'category' => 'general',
                'public' => true,
                'required' => true,
                'default' => '',
                'sort' => 2
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@edgeloan.example',
                'type' => 'email',
                'name' => 'Email de contact',
                'description' => 'Adresse email principale de contact',
                'category' => 'contact',
                'public' => true,
                'required' => true,
                'default' => '',
                'sort' => 1
            ],
            [
                'key' => 'contact_phone',
                'value' => '+33 1 23 45 67 89',
                'type' => 'string',
                'name' => 'Téléphone de contact',
                'description' => 'Numéro de téléphone principal',
                'category' => 'contact',
                'public' => true,
                'required' => true,
                'default' => '',
                'sort' => 2
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'name' => 'Mode maintenance',
                'description' => 'Activer/désactiver le mode maintenance',
                'category' => 'system',
                'public' => false,
                'required' => false,
                'default' => '0',
                'sort' => 1
            ],
            [
                'key' => 'max_loan_amount',
                'value' => '100000',
                'type' => 'integer',
                'name' => 'Montant maximum de prêt',
                'description' => 'Montant maximum autorisé pour un prêt',
                'category' => 'loan',
                'public' => true,
                'required' => true,
                'default' => '100000',
                'sort' => 1
            ],
            [
                'key' => 'min_loan_amount',
                'value' => '1000',
                'type' => 'integer',
                'name' => 'Montant minimum de prêt',
                'description' => 'Montant minimum autorisé pour un prêt',
                'category' => 'loan',
                'public' => true,
                'required' => true,
                'default' => '1000',
                'sort' => 2
            ],
        ];

        foreach ($settings as $settingData) {
            $setting = new Setting();
            $setting->setSettingKey($settingData['key']);
            $setting->setSettingValue($settingData['value']);
            $setting->setSettingType($settingData['type']);
            $setting->setSettingName($settingData['name']);
            $setting->setDescription($settingData['description']);
            $setting->setCategory($settingData['category']);
            $setting->setIsPublic($settingData['public']);
            $setting->setIsRequired($settingData['required']);
            $setting->setDefaultValue($settingData['default']);
            $setting->setSortOrder($settingData['sort']);
            $setting->setCreatedAt($now);
            $setting->setUpdatedAt($now);

            $manager->persist($setting);
        }

        $manager->flush();
    }
}