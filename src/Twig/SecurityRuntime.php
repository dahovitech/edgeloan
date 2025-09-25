<?php

namespace App\Twig;

use Twig\Extension\RuntimeExtensionInterface;
use Psr\Log\LoggerInterface;

/**
 * Security runtime for complex Twig operations
 * 
 * Handles security-sensitive operations that may require database access
 * or complex business logic
 */
class SecurityRuntime implements RuntimeExtensionInterface
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Validate and sanitize file upload references
     */
    public function validateFileReference(string $filePath): bool
    {
        try {
            // Normalize path to prevent directory traversal
            $normalizedPath = realpath($filePath);
            
            if ($normalizedPath === false) {
                $this->logger->warning('Invalid file path provided', ['path' => $filePath]);
                return false;
            }
            
            // Ensure file is within allowed directories
            $allowedBasePaths = [
                realpath(__DIR__ . '/../../public/uploads/'),
                realpath(__DIR__ . '/../../var/uploads/'),
            ];
            
            foreach ($allowedBasePaths as $basePath) {
                if ($basePath && str_starts_with($normalizedPath, $basePath)) {
                    return true;
                }
            }
            
            $this->logger->warning('File access outside allowed paths attempted', [
                'path' => $filePath,
                'normalized' => $normalizedPath
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('File validation error', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Advanced permission checking with logging
     */
    public function checkAdvancedPermissions($user, string $resource, array $context = []): bool
    {
        try {
            // Log access attempts for security auditing
            $this->logger->info('Permission check', [
                'user_id' => method_exists($user, 'getId') ? $user->getId() : 'anonymous',
                'resource' => $resource,
                'context' => $context
            ]);
            
            // Implementation would depend on your specific security requirements
            // This is a placeholder for more complex permission logic
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Permission check failed', [
                'resource' => $resource,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}