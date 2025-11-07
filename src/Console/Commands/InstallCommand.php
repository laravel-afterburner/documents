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

            // Add a comment section if it doesn't exist
            $comment = "\n# Cloudflare R2 Configuration for Afterburner Documents\n";
            if (strpos($envContent, '# Cloudflare R2 Configuration') === false) {
                $envContent .= $comment;
            }

            // Add each missing variable
            foreach ($added as $key) {
                $value = $this->envVariables[$key];
                $line = "{$key}={$value}\n";
                $envContent .= $line;
                $this->line("  ✓ Added: {$key}");
            }

            File::put($envPath, $envContent);

            // Also update .env.example if it exists
            if (File::exists($envExamplePath)) {
                $envExampleContent = File::get($envExamplePath);
                if (!$this->envVariableExists($envExampleContent, 'AFTERBURNER_DOCUMENTS_R2_ENDPOINT')) {
                    $envExampleContent .= $comment;
                    foreach ($added as $key) {
                        $value = $this->envVariables[$key];
                        $envExampleContent .= "{$key}={$value}\n";
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

