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
     * Format currency amounts safely
     */
    public function formatCurrency(?float $amount, string $currency = 'XOF'): string
    {
        if ($amount === null || !is_numeric($amount)) {
            return '0 ' . $currency;
        }
        
        // Ensure amount is positive for display
        $amount = abs((float)$amount);
        
        return number_format($amount, 0, ',', ' ') . ' ' . htmlspecialchars($currency, ENT_QUOTES);
    }
    
    /**
     * Format phone numbers safely
     */
    public function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return 'Non renseigné';
        }
        
        // Basic sanitization for phone display
        $cleaned = preg_replace('/[^0-9+\-\s()]/', '', $phone);
        
        return htmlspecialchars(trim($cleaned), ENT_QUOTES);
    }
    
    /**
     * Check if user can access loan data
     */
    public function canAccessLoan($user, $loan): bool
    {
        if (!$user || !$loan) {
            return false;
        }
        
        // Admin can access everything
        if (method_exists($user, 'getRoles') && 
            in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }
        
        // User can only access their own loans
        if (method_exists($loan, 'getCustomer') && 
            method_exists($user, 'getId')) {
            return $loan->getCustomer() && 
                   $loan->getCustomer()->getId() === $user->getId();
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
}