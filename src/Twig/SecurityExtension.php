<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Security-focused Twig extension for EdgeLoan
 * 
 * Provides safe data handling and formatting functions
 */
class SecurityExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('safe_user_data', [$this, 'sanitizeUserData']),
            new TwigFilter('currency_format', [$this, 'formatCurrency']),
            new TwigFilter('safe_phone', [$this, 'formatPhoneNumber']),
        ];
    }
    
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_access_loan', [$this, 'canAccessLoan']),
            new TwigFunction('user_initials', [$this, 'getUserInitials']),
            new TwigFunction('mask_sensitive_data', [$this, 'maskSensitiveData']),
            new TwigFunction('is_safe_url', [$this, 'isSafeUrl']),
        ];
    }
    
    /**
     * Securely sanitize user data for display
     */
    public function sanitizeUserData(?string $data): string
    {
        if ($data === null) {
            return '';
        }
        
        // Remove potentially dangerous characters and trim
        $cleaned = trim($data);
        
        // HTML escape with comprehensive flags
        return htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Format currency amounts safely with internationalization support
     */
    public function formatCurrency(?float $amount, string $currency = 'XOF', string $locale = 'fr_FR'): string
    {
        if ($amount === null || !is_numeric($amount)) {
            return '0 ' . htmlspecialchars($currency, ENT_QUOTES);
        }
        
        // Validate currency code format
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Invalid currency code format');
        }
        
        $amount = (float)$amount;
        
        // Different formatting based on currency
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
    
    /**
     * Format phone numbers safely with international support
     */
    public function formatPhoneNumber(?string $phone, string $defaultRegion = 'FR'): string
    {
        if (empty($phone)) {
            return 'Non renseigné';
        }
        
        // Remove all non-numeric characters except + and spaces for analysis
        $cleaned = preg_replace('/[^0-9+\s]/', '', trim($phone));
        
        if (empty($cleaned)) {
            return 'Numéro invalide';
        }
        
        // Basic international format detection and formatting
        if (str_starts_with($cleaned, '+')) {
            // International format
            $formatted = $this->formatInternationalPhone($cleaned);
        } elseif (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
            // French format
            $formatted = $this->formatFrenchPhone($cleaned);
        } elseif (strlen($cleaned) >= 8) {
            // Generic format for other cases
            $formatted = $this->formatGenericPhone($cleaned);
        } else {
            return htmlspecialchars($cleaned, ENT_QUOTES);
        }
        
        return htmlspecialchars($formatted, ENT_QUOTES);
    }
    
    private function formatInternationalPhone(string $phone): string
    {
        // Basic formatting for international numbers
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($digits, '33') && strlen($digits) === 11) {
            // French international format
            return '+33 ' . substr($digits, 2, 1) . ' ' . 
                   substr($digits, 3, 2) . ' ' . 
                   substr($digits, 5, 2) . ' ' . 
                   substr($digits, 7, 2) . ' ' . 
                   substr($digits, 9, 2);
        }
        
        return '+' . substr($digits, 0, 2) . ' ' . substr($digits, 2);
    }
    
    private function formatFrenchPhone(string $phone): string
    {
        return substr($phone, 0, 2) . ' ' . 
               substr($phone, 2, 2) . ' ' . 
               substr($phone, 4, 2) . ' ' . 
               substr($phone, 6, 2) . ' ' . 
               substr($phone, 8, 2);
    }
    
    private function formatGenericPhone(string $phone): string
    {
        // Simple generic formatting
        $chunks = str_split($phone, 2);
        return implode(' ', $chunks);
    }
    
    /**
     * Check if user can access loan data with comprehensive permission logic
     */
    public function canAccessLoan($user, $loan): bool
    {
        if (!$user || !$loan) {
            return false;
        }
        
        // Super admin can access everything
        if (method_exists($user, 'getRoles') && 
            in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }
        
        // Regular admin can access within their scope
        if (method_exists($user, 'getRoles') && 
            in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            
            // Additional check for admin scope if implemented
            if (method_exists($loan, 'getBranch') && method_exists($user, 'getBranch')) {
                return $loan->getBranch() === null || $loan->getBranch() === $user->getBranch();
            }
            
            return true;
        }
        
        // Employee can access loans they manage
        if (method_exists($user, 'getRoles') && 
            in_array('ROLE_EMPLOYEE', $user->getRoles(), true)) {
            
            if (method_exists($loan, 'getAssignedEmployee') && 
                method_exists($user, 'getId')) {
                return $loan->getAssignedEmployee() && 
                       $loan->getAssignedEmployee()->getId() === $user->getId();
            }
        }
        
        // Customer can access their own loans
        if (method_exists($loan, 'getCustomer') && 
            method_exists($user, 'getId')) {
            
            $customer = $loan->getCustomer();
            if ($customer && $customer->getId() === $user->getId()) {
                return true;
            }
            
            // Check for co-applicants/co-signers if the relationship exists
            if (method_exists($loan, 'getCoApplicants')) {
                foreach ($loan->getCoApplicants() as $coApplicant) {
                    if ($coApplicant->getId() === $user->getId()) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get user initials for avatars
     */
    public function getUserInitials($user): string
    {
        if (!$user) {
            return 'NN';
        }
        
        $initials = '';
        
        if (method_exists($user, 'getFirstName') && $user->getFirstName()) {
            $initials .= strtoupper(substr($this->sanitizeUserData($user->getFirstName()), 0, 1));
        }
        
        if (method_exists($user, 'getLastName') && $user->getLastName()) {
            $initials .= strtoupper(substr($this->sanitizeUserData($user->getLastName()), 0, 1));
        }
        
        if (empty($initials) && method_exists($user, 'getEmail')) {
            $initials = strtoupper(substr($this->sanitizeUserData($user->getEmail()), 0, 2));
        }
        
        return $initials ?: 'NN';
    }
    
    /**
     * Mask sensitive data for display (e.g., credit card, IBAN)
     */
    public function maskSensitiveData(?string $data, string $type = 'default', int $visibleChars = 4): string
    {
        if (empty($data)) {
            return '';
        }
        
        $cleaned = trim($data);
        $length = strlen($cleaned);
        
        if ($length <= $visibleChars) {
            return str_repeat('*', $length);
        }
        
        switch ($type) {
            case 'iban':
                // Show first 4 and last 4 characters for IBAN
                return substr($cleaned, 0, 4) . str_repeat('*', $length - 8) . substr($cleaned, -4);
                
            case 'card':
                // Show last 4 digits for credit card
                return str_repeat('*', $length - 4) . substr($cleaned, -4);
                
            case 'phone':
                // Show first 2 and last 2 digits for phone
                return substr($cleaned, 0, 2) . str_repeat('*', $length - 4) . substr($cleaned, -2);
                
            default:
                // Generic masking - show first chars and mask the rest
                return substr($cleaned, 0, $visibleChars) . str_repeat('*', $length - $visibleChars);
        }
    }
    
    /**
     * Check if URL is safe for redirects and links
     */
    public function isSafeUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        
        // Allow relative URLs
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }
        
        // Parse URL to check components
        $parsed = parse_url($url);
        
        if ($parsed === false) {
            return false;
        }
        
        // Only allow http and https schemes
        if (isset($parsed['scheme']) && !in_array($parsed['scheme'], ['http', 'https'], true)) {
            return false;
        }
        
        // Blacklist potentially dangerous hosts
        if (isset($parsed['host'])) {
            $dangerousHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
            if (in_array(strtolower($parsed['host']), $dangerousHosts, true)) {
                return false;
            }
        }
        
        return true;
    }
}