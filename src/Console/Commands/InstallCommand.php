<?php

namespace Afterburner\Documents\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'afterburner:documents:install 
                            {--skip-env : Skip adding environment variables to .env file}
                            {--skip-publish : Skip publishing config, migrations, and views}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Afterburner Documents package';

    /**
     * Environment variables that should be added to .env
     *
     * @var array
     */
    protected $envVariables = [
        'AFTERBURNER_DOCUMENTS_R2_ENDPOINT' => 'https://<your-account-id>.r2.cloudflarestorage.com',
        'AFTERBURNER_DOCUMENTS_R2_ACCESS_KEY_ID' => 'your-access-key-id',
        'AFTERBURNER_DOCUMENTS_R2_SECRET_ACCESS_KEY' => 'your-secret-access-key',
        'AFTERBURNER_DOCUMENTS_R2_BUCKET' => 'your-bucket-name',
        'AFTERBURNER_DOCUMENTS_R2_REGION' => 'auto',
        'AFTERBURNER_DOCUMENTS_R2_URL' => 'https://your-bucket-domain.com  (Optional: if you set up a custom domain)',
        'AFTERBURNER_DOCUMENTS_R2_USE_PATH_STYLE_ENDPOINT' => 'false',
        'AFTERBURNER_DOCUMENTS_MAX_FILE_SIZE' => '2147483648',
        'AFTERBURNER_DOCUMENTS_MAX_CHUNKS' => '5000',
        'AFTERBURNER_DOCUMENTS_CHUNK_SIZE' => '5242880',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Afterburner Documents package...');
        $this->newLine();

        // Publish assets
        if (!$this->option('skip-publish')) {
            $this->publishAssets();
        }

        // Add environment variables
        if (!$this->option('skip-env')) {
            $this->addEnvironmentVariables();
        }

        $this->newLine();
        $this->info('✓ Installation complete!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->line('  1. Update your .env file with your Cloudflare R2 credentials');
        $this->line('  2. Run migrations: php artisan migrate');
        $this->line('  3. Follow the setup guide: See CLOUDFLARE_R2_SETUP.md');

        return Command::SUCCESS;
    }

    /**
     * Publish package assets (config, migrations, views).
     */
    protected function publishAssets(): void
    {
        $this->info('Publishing package assets...');

        // Publish config
        if (!$this->call('vendor:publish', [
            '--tag' => 'afterburner-documents-config',
            '--force' => false,
        ])) {
            $this->line('  ✓ Config published');
        }

        // Publish migrations
        if (!$this->call('vendor:publish', [
            '--tag' => 'afterburner-documents-migrations',
            '--force' => false,
        ])) {
            $this->line('  ✓ Migrations published');
        }

        // Publish views
        if (!$this->call('vendor:publish', [
            '--tag' => 'afterburner-documents-assets',
            '--force' => false,
        ])) {
            $this->line('  ✓ Views published');
        }

        $this->newLine();
    }

    /**
     * Add environment variables to .env file.
     */
    protected function addEnvironmentVariables(): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        // Check if .env exists
        if (!File::exists($envPath)) {
            $this->warn('  ⚠ .env file not found. Skipping environment variable setup.');
            $this->line('  You can manually add the variables later. See CLOUDFLARE_R2_SETUP.md');
            return;
        }

        $envContent = File::get($envPath);
        $added = [];
        $existing = [];

        // Check each variable
        foreach ($this->envVariables as $key => $defaultValue) {
            if ($this->envVariableExists($envContent, $key)) {
                $existing[] = $key;
            } else {
                $added[] = $key;
            }
        }

        // Add missing variables
        if (!empty($added)) {
            $this->info('Adding environment variables to .env...');

            // Define comment sections
            $r2Comment = "\n# Cloudflare R2 Configuration for Afterburner Documents\n";
            $uploadComment = "\n# Upload Configuration for Afterburner Documents\n";
            $uploadLimitsComment = "# Upload Limits (max_file_size in bytes, max_chunks per upload, chunk_size in bytes)\n";
            $uploadDefaultsComment = "# Default: 2GB max file size, 5000 max chunks, 5MB chunk size\n";
            
            // Define upload keys
            $uploadKeys = ['AFTERBURNER_DOCUMENTS_MAX_FILE_SIZE', 'AFTERBURNER_DOCUMENTS_MAX_CHUNKS', 'AFTERBURNER_DOCUMENTS_CHUNK_SIZE'];
            $uploadAdded = array_intersect($added, $uploadKeys);
            $r2Added = array_diff($added, $uploadKeys);
            
            // Add comment sections if they don't exist
            if (strpos($envContent, '# Cloudflare R2 Configuration') === false && !empty($r2Added)) {
                $envContent .= $r2Comment;
            }
            
            if (strpos($envContent, '# Upload Configuration') === false && !empty($uploadAdded)) {
                $envContent .= $uploadComment;
            }
            
            // Add R2 variables first
            foreach ($r2Added as $key) {
                $value = $this->envVariables[$key];
                $line = "{$key}={$value}\n";
                $envContent .= $line;
                $this->line("  ✓ Added: {$key}");
            }
            
            // Add upload variables with comments
            if (!empty($uploadAdded)) {
                $envContent .= $uploadLimitsComment;
                $envContent .= $uploadDefaultsComment;
                foreach ($uploadAdded as $key) {
                    $value = $this->envVariables[$key];
                    $line = "{$key}={$value}\n";
                    $envContent .= $line;
                    $this->line("  ✓ Added: {$key}");
                }
            }

            File::put($envPath, $envContent);

            // Also update .env.example if it exists
            if (File::exists($envExamplePath)) {
                $envExampleContent = File::get($envExamplePath);
                $needsUpdate = false;
                
                // Check if any R2 variables are missing
                if (!$this->envVariableExists($envExampleContent, 'AFTERBURNER_DOCUMENTS_R2_ENDPOINT') && !empty($r2Added)) {
                    $envExampleContent .= $r2Comment;
                    $needsUpdate = true;
                }
                
                // Check if any upload variables are missing
                $missingUpload = array_filter($uploadKeys, function($key) use ($envExampleContent) {
                    return !$this->envVariableExists($envExampleContent, $key);
                });
                
                if (!empty($missingUpload)) {
                    if (strpos($envExampleContent, '# Upload Configuration') === false) {
                        $envExampleContent .= $uploadComment;
                    }
                    $envExampleContent .= $uploadLimitsComment;
                    $envExampleContent .= $uploadDefaultsComment;
                    $needsUpdate = true;
                }
                
                if ($needsUpdate) {
                    foreach ($added as $key) {
                        if (!$this->envVariableExists($envExampleContent, $key)) {
                            $value = $this->envVariables[$key];
                            $envExampleContent .= "{$key}={$value}\n";
                        }
                    }
                    File::put($envExamplePath, $envExampleContent);
                    $this->line("  ✓ Updated .env.example");
                }
            }
        } else {
            $this->info('Environment variables already exist in .env');
        }

        if (!empty($existing)) {
            $this->line('  (Skipped existing variables: ' . implode(', ', $existing) . ')');
        }

        $this->newLine();
    }

    /**
     * Check if an environment variable exists in the content.
     */
    protected function envVariableExists(string $content, string $key): bool
    {
        // Check for the key at the start of a line (with optional spaces before it)
        // This handles cases like: KEY=value or KEY = value
        return preg_match("/^\s*{$key}\s*=/m", $content) === 1;
    }
}

