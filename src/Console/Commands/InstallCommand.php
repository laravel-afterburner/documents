<?php

namespace Afterburner\Documents\Console\Commands;

use Afterburner\Documents\Database\Seeders\DocumentPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'afterburner:documents:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Afterburner Documents package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Afterburner Documents package...');

        // Publish configuration
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'afterburner-documents-config',
            '--force' => true,
        ]);

        // Publish migrations
        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'afterburner-documents-migrations',
            '--force' => true,
        ]);

        // Publish views
        $this->info('Publishing views...');
        $this->call('vendor:publish', [
            '--tag' => 'afterburner-documents-assets',
            '--force' => true,
        ]);

        // Add environment variables
        $this->info('Adding environment variables...');
        $this->addEnvironmentVariables();

        // Run migrations
        if ($this->confirm('Run migrations now?', true)) {
            $this->info('Running migrations...');
            $this->call('migrate');
        }

        // Seed permissions
        if ($this->confirm('Seed document permissions?', true)) {
            $this->info('Seeding document permissions...');
            $seeder = new DocumentPermissionsSeeder();
            $seeder->setCommand($this);
            $seeder->run();
        }

        $this->info('Installation complete!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->comment('1. Configure your Cloudflare R2 credentials in .env');
        $this->comment('2. The R2 disk has been automatically configured from config/afterburner-documents.php');
        $this->comment('3. Visit /teams/{team}/documents to start using documents');

        return Command::SUCCESS;
    }

    /**
     * Add environment variables to .env and .env.example.
     */
    protected function addEnvironmentVariables(): void
    {
        $envVars = [
            '',
            '# Afterburner Documents Configuration',
            'AFTERBURNER_DOCUMENTS_ENABLED=true',
            'AFTERBURNER_DOCUMENTS_R2_ENDPOINT=',
            'AFTERBURNER_DOCUMENTS_R2_ACCESS_KEY_ID=',
            'AFTERBURNER_DOCUMENTS_R2_SECRET_ACCESS_KEY=',
            'AFTERBURNER_DOCUMENTS_R2_BUCKET=',
            'AFTERBURNER_DOCUMENTS_R2_REGION=auto',
            'AFTERBURNER_DOCUMENTS_R2_URL=',
            'AFTERBURNER_DOCUMENTS_R2_USE_PATH_STYLE_ENDPOINT=false',
            'AFTERBURNER_DOCUMENTS_CHUNK_SIZE=5242880',
            'AFTERBURNER_DOCUMENTS_MAX_FILE_SIZE=2147483648',
            'AFTERBURNER_DOCUMENTS_MAX_CHUNKS=5000',
            'AFTERBURNER_DOCUMENTS_STORAGE_PATH=documents/{team_id}/{year}/{month}/{document_id}',
            'AFTERBURNER_DOCUMENTS_VERSIONING_ENABLED=true',
            'AFTERBURNER_DOCUMENTS_AUTO_VERSION_ON_UPDATE=true',
            'AFTERBURNER_DOCUMENTS_SEARCH_ENABLED=true',
        ];

        // Add to .env if it exists
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $envContent = File::get($envPath);
            foreach ($envVars as $var) {
                if ($var && !str_contains($envContent, explode('=', $var)[0])) {
                    File::append($envPath, "\n".$var);
                }
            }
        }

        // Add to .env.example if it exists
        $envExamplePath = base_path('.env.example');
        if (File::exists($envExamplePath)) {
            $envExampleContent = File::get($envExamplePath);
            foreach ($envVars as $var) {
                if ($var && !str_contains($envExampleContent, explode('=', $var)[0])) {
                    File::append($envExamplePath, "\n".$var);
                }
            }
        }
    }
}

