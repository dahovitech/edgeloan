<?php

namespace App\Tests\Translations;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Helper class to track YAML key hierarchy and detect duplicates
 */
class YamlKeyTracker
{
    private array $hierarchy = [];
    private array $currentPath = [];
    
    public function addKey(int $indent, string $key, int $lineNumber): void
    {
        // Update current path based on indentation
        $this->updateCurrentPath($indent);
        
        // Add current key to path
        $this->currentPath[$indent] = $key;
        
        // Build full path for this key
        $fullPath = $this->buildFullPath($indent);
        
        // Check for duplicates at this level
        if (isset($this->hierarchy[$fullPath])) {
            throw new \InvalidArgumentException(
                "Previous occurrence at line {$this->hierarchy[$fullPath]}"
            );
        }
        
        $this->hierarchy[$fullPath] = $lineNumber;
    }
    
    private function updateCurrentPath(int $indent): void
    {
        // Remove deeper levels when indentation decreases
        $this->currentPath = array_filter(
            $this->currentPath,
            fn($level) => $level <= $indent,
            ARRAY_FILTER_USE_KEY
        );
    }
    
    private function buildFullPath(int $indent): string
    {
        $pathElements = [];
        
        // Build hierarchical path
        ksort($this->currentPath);
        foreach ($this->currentPath as $level => $key) {
            if ($level <= $indent) {
                $pathElements[] = $key;
            }
        }
        
        return implode('.', $pathElements);
    }
}

/**
 * Test to validate all YAML translation files for syntax errors and duplicate keys
 */
class YamlValidationTest extends KernelTestCase
{
    public function testYamlTranslationFilesAreValid(): void
    {
        $translationsDir = self::getContainer()->getParameter('kernel.project_dir') . '/translations';
        $yamlFiles = glob($translationsDir . '/*.yaml');
        
        $this->assertGreaterThan(0, count($yamlFiles), 'No YAML files found in translations directory');
        
        foreach ($yamlFiles as $file) {
            try {
                $content = Yaml::parseFile($file);
                $this->assertIsArray($content, "File {$file} does not contain valid YAML structure");
                
                // Check for duplicate keys
                $this->assertNoDuplicateKeys($file);
                
            } catch (ParseException $e) {
                $this->fail("YAML error in {$file}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Check for duplicate keys in YAML file using proper hierarchical parsing
     */
    private function assertNoDuplicateKeys(string $file): void
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $keyTracker = new YamlKeyTracker();
        
        foreach ($lines as $lineNum => $line) {
            // Skip comments, empty lines, and list items
            $trimmed = trim($line);
            if (empty($trimmed) || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '-')) {
                continue;
            }
            
            // Match YAML key pattern (more comprehensive regex)
            if (preg_match('/^(\s*)([a-zA-Z_][a-zA-Z0-9_\-\.]*)\s*:\s*(.*)$/', $line, $matches)) {
                $indent = strlen($matches[1]);
                $key = $matches[2];
                $value = trim($matches[3]);
                
                try {
                    $keyTracker->addKey($indent, $key, $lineNum + 1);
                } catch (\InvalidArgumentException $e) {
                    $this->fail("Duplicate key '{$key}' found at line " . ($lineNum + 1) . " in {$file}. " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Test that all required translation keys exist in both French and English
     */
    public function testTranslationKeysConsistency(): void
    {
        $frFile = self::getContainer()->getParameter('kernel.project_dir') . '/translations/messages.fr.yaml';
        $enFile = self::getContainer()->getParameter('kernel.project_dir') . '/translations/messages.en.yaml';
        
        if (!file_exists($frFile) || !file_exists($enFile)) {
            $this->markTestSkipped('French or English translation files not found');
        }
        
        $frData = Yaml::parseFile($frFile);
        $enData = Yaml::parseFile($enFile);
        
        $frKeys = $this->flattenKeys($frData);
        $enKeys = $this->flattenKeys($enData);
        
        // Check for keys missing in English
        $missingInEnglish = array_diff($frKeys, $enKeys);
        if (!empty($missingInEnglish)) {
            $this->fail('Keys missing in English translations: ' . implode(', ', array_slice($missingInEnglish, 0, 10)));
        }
        
        // Check for keys missing in French  
        $missingInFrench = array_diff($enKeys, $frKeys);
        if (!empty($missingInFrench)) {
            $this->fail('Keys missing in French translations: ' . implode(', ', array_slice($missingInFrench, 0, 10)));
        }
    }
    
    /**
     * Flatten nested array keys into dot notation with type safety
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        
        foreach ($array as $key => $value) {
            // Ensure key is string to avoid issues with numeric keys
            $stringKey = (string)$key;
            $fullKey = $prefix ? "{$prefix}.{$stringKey}" : $stringKey;
            
            if (is_array($value) && !empty($value)) {
                // Only recurse if array is not empty and associative
                if (array_keys($value) !== range(0, count($value) - 1)) {
                    $keys = array_merge($keys, $this->flattenKeys($value, $fullKey));
                } else {
                    // Handle indexed arrays as leaf nodes
                    $keys[] = $fullKey;
                }
            } else {
                $keys[] = $fullKey;
            }
        }
        
        return array_unique($keys);
    }
}
}