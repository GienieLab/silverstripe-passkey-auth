<?php

namespace GienieLab\PasskeyAuth\Task;

use GienieLab\PasskeyAuth\Model\PasskeyCredential;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class PasskeySSecurityMigrationTask extends BuildTask
{
    protected string $title = 'Passkey Security Migration Task'; 
    
    protected static string $description = 'Migrates existing passkey credentials to new security fields and validates data integrity';

     protected static string $commandName  = 'passkey-security-migration';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $logger = Injector::inst()->get(LoggerInterface::class);
        
        echo "Starting Passkey Security Migration...\n";
        
        $credentials = PasskeyCredential::get();
        $migrated = 0;
        $errors = 0;
        
        foreach ($credentials as $credential) {
            try {
                // Set default values for new fields
                if ($credential->IsActive === null) {
                    $credential->IsActive = true;
                }
                
                if (empty($credential->CredentialName)) {
                    $credential->CredentialName = 'Passkey ' . $credential->dbObject('Created')->Nice();
                }
                
                // Validate credential data integrity
                if (empty($credential->CredentialID) || empty($credential->PublicKey)) {
                    echo "⚠️ Warning: Credential {$credential->ID} has missing required data\n";
                    $errors++;
                    continue;
                }
                
                // Validate base64 encoding of sensitive data
                if (!base64_decode($credential->PublicKey, true)) {
                    echo "⚠️ Warning: Credential {$credential->ID} has invalid PublicKey encoding\n";
                    $errors++;
                    continue;
                }
                
                $credential->write();
                $migrated++;
                
                echo "✓ Migrated credential {$credential->ID}\n";
                return Command::SUCCESS;
                
            } catch (\Exception $e) {
                echo "❌ Error migrating credential {$credential->ID}: " . $e->getMessage() . "\n";
                $errors++;
                
                $logger->error('Passkey migration error', [
                    'credential_id' => $credential->ID,
                    'error' => $e->getMessage()
                ]);
                 return Command::FAILURE;
            }
        }
        
        echo "\n=== Migration Summary ===\n";
        echo "✓ Successfully migrated: {$migrated} credentials\n";
        echo "❌ Errors: {$errors} credentials\n";
        
        if ($errors > 0) {
            echo "\n⚠️ Some credentials could not be migrated. Please review the errors above.\n";
        } else {
            echo "\n🎉 All credentials migrated successfully!\n";
        }
        
        $logger->info('Passkey security migration completed', [
            'migrated' => $migrated,
            'errors' => $errors
        ]);
        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    
    }
}
