<?php

namespace App\Tests\Translations;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

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
     * Check for duplicate keys in YAML file
     */
    private function assertNoDuplicateKeys(string $file): void
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $keys = [];
        $currentLevel = 0;
        
        foreach ($lines as $lineNum => $line) {
            // Skip comments and empty lines
            if (empty(trim($line)) || str_starts_with(trim($line), '#')) {
                continue;
            }
            
            // Match YAML key pattern
            if (preg_match('/^(\s*)([a-zA-Z_][a-zA-Z0-9_]*):/', $line, $matches)) {
                $indent = strlen($matches[1]);
                $key = $matches[2];
                
                // Create hierarchical key based on indentation
                $fullKey = $this->buildFullKey($keys, $indent, $key);
                
                if (isset($keys[$fullKey])) {
                    $this->fail("Duplicate key '{$key}' found at line " . ($lineNum + 1) . " in {$file}. Previous occurrence at line {$keys[$fullKey]}");
                }
                
                $keys[$fullKey] = $lineNum + 1;
            }
        }
    }
    
    /**
     * Build hierarchical key for proper duplicate detection
     */
    private function buildFullKey(array $keys, int $indent, string $key): string
    {
        // Simple implementation - for more complex hierarchies, this could be enhanced
        return "{$indent}:{$key}";
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
     * Flatten nested array keys into dot notation
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }
        
        return $keys;
    }
}