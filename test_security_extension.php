<?php
// Test simple de notre extension de sécurité

// Mock de la classe SecurityExtension pour test
class SecurityExtension
{
    public function sanitizeUserData(?string $data): string
    {
        if ($data === null) {
            return '';
        }
        
        $cleaned = trim($data);
        return htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public function formatCurrency(?float $amount, string $currency = 'XOF'): string
    {
        if ($amount === null || !is_numeric($amount)) {
            return '0 ' . htmlspecialchars($currency, ENT_QUOTES);
        }
        
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Invalid currency code format');
        }
        
        $amount = (float)$amount;
        
        switch ($currency) {
            case 'EUR':
            case 'USD':
                $formatted = number_format($amount, 2, ',', ' ');
                break;
            case 'XOF':
            case 'XAF':
                $formatted = number_format($amount, 0, ',', ' ');
                break;
            default:
                $formatted = number_format($amount, 2, ',', ' ');
        }
        
        return $formatted . ' ' . htmlspecialchars($currency, ENT_QUOTES);
    }
    
    public function isSafeUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        
        // Allow relative URLs
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }
        
        $parsed = parse_url($url);
        
        if ($parsed === false) {
            return false;
        }
        
        if (isset($parsed['scheme']) && !in_array($parsed['scheme'], ['http', 'https'], true)) {
            return false;
        }
        
        if (isset($parsed['host'])) {
            $dangerousHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
            if (in_array(strtolower($parsed['host']), $dangerousHosts, true)) {
                return false;
            }
        }
        
        return true;
    }
}

// Tests
$extension = new SecurityExtension();

echo "=== TEST SANITIZATION ===\n";
$test1 = $extension->sanitizeUserData('<script>alert("xss")</script>');
echo "XSS Input: " . $test1 . "\n";
echo "Expected: &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;\n";
echo "✅ " . ($test1 === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;' ? 'PASSED' : 'FAILED') . "\n\n";

echo "=== TEST CURRENCY FORMATTING ===\n";
$test2 = $extension->formatCurrency(1250.75, 'EUR');
echo "Currency EUR: " . $test2 . "\n";
echo "Expected: 1 250,75 EUR\n";
echo "✅ " . ($test2 === '1 250,75 EUR' ? 'PASSED' : 'FAILED') . "\n\n";

$test3 = $extension->formatCurrency(1250, 'XOF');
echo "Currency XOF: " . $test3 . "\n";
echo "Expected: 1 250 XOF\n";
echo "✅ " . ($test3 === '1 250 XOF' ? 'PASSED' : 'FAILED') . "\n\n";

echo "=== TEST URL SAFETY ===\n";
$safeUrl = $extension->isSafeUrl('/dashboard');
echo "Relative URL /dashboard: " . ($safeUrl ? 'SAFE' : 'UNSAFE') . "\n";
echo "✅ " . ($safeUrl ? 'PASSED' : 'FAILED') . "\n\n";

$dangerousUrl = $extension->isSafeUrl('javascript:alert(1)');
echo "JavaScript URL: " . ($dangerousUrl ? 'SAFE' : 'UNSAFE') . "\n";
echo "✅ " . (!$dangerousUrl ? 'PASSED' : 'FAILED') . "\n\n";

echo "=== ALL TESTS COMPLETED ===\n";