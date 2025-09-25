<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Find all settings grouped by category
     *
     * @return array<string, Setting[]>
     */
    public function findByCategory(): array
    {
        $settings = $this->createQueryBuilder('s')
            ->orderBy('s.category', 'ASC')
            ->addOrderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.settingName', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $grouped[$setting->getCategory()][] = $setting;
        }

        return $grouped;
    }

    /**
     * Find settings by category
     *
     * @param string $category
     * @return Setting[]
     */
    public function findByCategoryName(string $category): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.category = :category')
            ->setParameter('category', $category)
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.settingName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all public settings (accessible from frontend)
     *
     * @return Setting[]
     */
    public function findPublicSettings(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isPublic = :public')
            ->setParameter('public', true)
            ->orderBy('s.category', 'ASC')
            ->addOrderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.settingName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all public settings as key-value pairs
     *
     * @return array<string, mixed>
     */
    public function findPublicSettingsAsArray(): array
    {
        $settings = $this->findPublicSettings();
        $result = [];

        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $setting->getValue();
        }

        return $result;
    }

    /**
     * Find setting by key
     */
    public function findByKey(string $key): ?Setting
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.settingKey = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get setting value by key (with fallback to default)
     */
    public function getValueByKey(string $key, mixed $fallback = null): mixed
    {
        $setting = $this->findByKey($key);

        if (!$setting) {
            return $fallback;
        }

        $value = $setting->getValue();
        return $value !== null ? $value : $fallback;
    }

    /**
     * Set setting value by key
     */
    public function setValueByKey(string $key, mixed $value): bool
    {
        $setting = $this->findByKey($key);

        if (!$setting) {
            return false;
        }

        $setting->setTypedValue($value);
        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Get all settings as a key-value array
     *
     * @return array<string, mixed>
     */
    public function getAllSettingsAsArray(): array
    {
        $settings = $this->findAll();
        $result = [];

        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $setting->getValue();
        }

        return $result;
    }

    /**
     * Find required settings that have no value
     *
     * @return Setting[]
     */
    public function findMissingRequiredSettings(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isRequired = :required')
            ->andWhere('s.settingValue IS NULL OR s.settingValue = :empty')
            ->setParameter('required', true)
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all unique categories
     *
     * @return string[]
     */
    public function getCategories(): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('DISTINCT s.category')
            ->orderBy('s.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'category');
    }

    /**
     * Bulk update settings by category
     *
     * @param string $category
     * @param array<string, mixed> $values
     */
    public function updateSettingsByCategory(string $category, array $values): int
    {
        $updated = 0;
        $settings = $this->findByCategoryName($category);

        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $key = $setting->getSettingKey();
            if (array_key_exists($key, $values)) {
                $setting->setTypedValue($values[$key]);
                $this->getEntityManager()->persist($setting);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->getEntityManager()->flush();
        }

        return $updated;
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
        $setting = $this->findByKey($key);

        if (!$setting) {
            $setting = new Setting();
            $setting->setSettingKey($key);
            $setting->setSettingType($type);
            $setting->setSettingName($name ?? $key);
            $setting->setDescription($description);
            $setting->setCategory($category);
            $setting->setIsPublic($isPublic);
            $setting->setIsRequired($isRequired);
            if ($defaultValue !== null) {
                $setting->setDefaultValue(
                    $type === Setting::TYPE_JSON ? json_encode($defaultValue) : (string) $defaultValue
                );
            }
        }

        $setting->setTypedValue($value);
        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();

        return $setting;
    }
}