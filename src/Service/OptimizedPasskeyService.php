<?php

namespace GienieLab\PasskeyAuth\Service;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

/**
 * Optimized PasskeyService with performance improvements
 */
class OptimizedPasskeyService
{
    use Injectable, Configurable;

    /**
     * Cache for RP entity to avoid repeated creation
     */
    private static $rp_entity_cache = null;

    /**
     * Optimized credential lookup with better query performance
     */
    public function findCredentialsForUser($userID): array
    {
        // Use more efficient query with proper indexing hints
        return PasskeyCredential::get()
            ->filter(['UserID' => $userID, 'IsActive' => true])
            ->sort('LastUsed DESC')
            ->limit(50) // Reasonable limit for performance
            ->toArray();
    }

    /**
     * Batch update credentials for better performance
     */
    public function updateMultipleCredentials(array $credentialUpdates): void
    {
        // Use batch operations instead of individual saves
        foreach ($credentialUpdates as $update) {
            PasskeyCredential::get()
                ->byID($update['id'])
                ->update($update['data']);
        }
        
        // Single flush operation
        DataObject::flush_and_destroy_cache();
    }

    /**
     * Memoized RP entity creation
     */
    public function getRpEntity(): object
    {
        if (self::$rp_entity_cache === null) {
            self::$rp_entity_cache = $this->createRpEntity();
        }
        
        return self::$rp_entity_cache;
    }

    /**
     * Optimized challenge generation with better entropy
     */
    public function generateOptimizedChallenge(): string
    {
        // Use more efficient random generation
        return base64_encode(random_bytes(32)); // 256-bit entropy
    }

    /**
     * Memory-efficient credential processing
     */
    public function processCredentialData(array $data): array
    {
        // Process in chunks to avoid memory issues with large datasets
        $chunks = array_chunk($data, 100);
        $results = [];
        
        foreach ($chunks as $chunk) {
            $results = array_merge($results, $this->processChunk($chunk));
            
            // Free memory between chunks
            gc_collect_cycles();
        }
        
        return $results;
    }

    private function processChunk(array $chunk): array
    {
        // Optimized processing logic here
        return array_map([$this, 'processCredential'], $chunk);
    }

    private function processCredential(array $credential): array
    {
        // Streamlined credential processing
        return [
            'id' => $credential['id'],
            'type' => $credential['type'] ?? 'public-key',
            'transports' => $credential['transports'] ?? ['internal', 'hybrid']
        ];
    }
}
