<?php

namespace App\Tests\Twig;

use App\Twig\SecurityExtension;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for SecurityExtension
 */
class SecurityExtensionTest extends TestCase
{
    private SecurityExtension $extension;
    
    protected function setUp(): void
    {
        $this->extension = new SecurityExtension();
    }
    
    public function testSanitizeUserData(): void
    {
        // Test normal data
        $this->assertEquals('John Doe', $this->extension->sanitizeUserData('John Doe'));
        
        // Test XSS prevention
        $this->assertEquals('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', 
                           $this->extension->sanitizeUserData('<script>alert(\'xss\')</script>'));
        
        // Test null input
        $this->assertEquals('', $this->extension->sanitizeUserData(null));
        
        // Test empty string
        $this->assertEquals('', $this->extension->sanitizeUserData(''));
    }
    
    public function testFormatCurrency(): void
    {
        // Test XOF currency
        $this->assertEquals('1 000 XOF', $this->extension->formatCurrency(1000, 'XOF'));
        
        // Test EUR currency with decimals
        $this->assertEquals('1 000,50 EUR', $this->extension->formatCurrency(1000.50, 'EUR'));
        
        // Test null input
        $this->assertEquals('0 XOF', $this->extension->formatCurrency(null));
        
        // Test invalid currency code
        $this->expectException(\InvalidArgumentException::class);
        $this->extension->formatCurrency(1000, 'INVALID');
    }
    
    public function testFormatPhoneNumber(): void
    {
        // Test French phone number
        $this->assertEquals('01 23 45 67 89', $this->extension->formatPhoneNumber('0123456789'));
        
        // Test international format
        $this->assertEquals('+33 1 23 45 67 89', $this->extension->formatPhoneNumber('+33123456789'));
        
        // Test empty input
        $this->assertEquals('Non renseigné', $this->extension->formatPhoneNumber(''));
        
        // Test null input
        $this->assertEquals('Non renseigné', $this->extension->formatPhoneNumber(null));
        
        // Test invalid input
        $this->assertEquals('Numéro invalide', $this->extension->formatPhoneNumber('abc'));
    }
    
    public function testMaskSensitiveData(): void
    {
        // Test IBAN masking
        $this->assertEquals('FR76************1234', 
                           $this->extension->maskSensitiveData('FR7612345678901234567890123456', 'iban'));
        
        // Test card masking
        $this->assertEquals('************1234', 
                           $this->extension->maskSensitiveData('1234567890123456', 'card'));
        
        // Test phone masking
        $this->assertEquals('01******89', 
                           $this->extension->maskSensitiveData('0123456789', 'phone'));
        
        // Test empty input
        $this->assertEquals('', $this->extension->maskSensitiveData(''));
    }
    
    public function testIsSafeUrl(): void
    {
        // Test relative URLs
        $this->assertTrue($this->extension->isSafeUrl('/dashboard'));
        $this->assertTrue($this->extension->isSafeUrl('/loan/apply'));
        
        // Test absolute safe URLs
        $this->assertTrue($this->extension->isSafeUrl('https://example.com'));
        $this->assertTrue($this->extension->isSafeUrl('http://example.com'));
        
        // Test dangerous URLs
        $this->assertFalse($this->extension->isSafeUrl('//evil.com'));
        $this->assertFalse($this->extension->isSafeUrl('javascript:alert(1)'));
        $this->assertFalse($this->extension->isSafeUrl('data:text/html,<script>'));
        $this->assertFalse($this->extension->isSafeUrl('ftp://example.com'));
        
        // Test localhost URLs
        $this->assertFalse($this->extension->isSafeUrl('http://localhost/'));
        $this->assertFalse($this->extension->isSafeUrl('http://127.0.0.1/'));
        
        // Test empty/null input
        $this->assertFalse($this->extension->isSafeUrl(''));
        $this->assertFalse($this->extension->isSafeUrl(null));
    }
    
    public function testCanAccessLoan(): void
    {
        // Mock user with admin role
        $adminUser = $this->createMockUser(['ROLE_SUPER_ADMIN'], 1);
        $regularUser = $this->createMockUser(['ROLE_USER'], 2);
        $loan = $this->createMockLoan(2); // Loan belongs to user ID 2
        
        // Test admin access
        $this->assertTrue($this->extension->canAccessLoan($adminUser, $loan));
        
        // Test owner access
        $this->assertTrue($this->extension->canAccessLoan($regularUser, $loan));
        
        // Test unauthorized access
        $unauthorizedUser = $this->createMockUser(['ROLE_USER'], 3);
        $this->assertFalse($this->extension->canAccessLoan($unauthorizedUser, $loan));
        
        // Test null inputs
        $this->assertFalse($this->extension->canAccessLoan(null, $loan));
        $this->assertFalse($this->extension->canAccessLoan($regularUser, null));
    }
    
    private function createMockUser(array $roles, int $id): object
    {
        return new class($roles, $id) {
            private array $roles;
            private int $id;
            
            public function __construct(array $roles, int $id) {
                $this->roles = $roles;
                $this->id = $id;
            }
            
            public function getRoles(): array {
                return $this->roles;
            }
            
            public function getId(): int {
                return $this->id;
            }
        };
    }
    
    private function createMockLoan(int $customerId): object
    {
        return new class($customerId) {
            private int $customerId;
            
            public function __construct(int $customerId) {
                $this->customerId = $customerId;
            }
            
            public function getCustomer(): object {
                return new class($this->customerId) {
                    private int $id;
                    
                    public function __construct(int $id) {
                        $this->id = $id;
                    }
                    
                    public function getId(): int {
                        return $this->id;
                    }
                };
            }
        };
    }
}