<?php

namespace Afterburner\Documents;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class DocumentsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Check if template is installed (optional safety check)
        if (!class_exists(\App\Models\Team::class)) {
            return;
        }

        $this->mergeConfigFrom(
            __DIR__.'/../config/afterburner-documents.php',
            'afterburner-documents'
        );

        // Register R2 filesystem disk early (before filesystem service provider boots)
        $this->registerR2Disk();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Afterburner\Documents\Console\Commands\InstallCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Check if template is installed
        if (!class_exists(\App\Models\Team::class)) {
            return;
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/afterburner-documents.php' => config_path('afterburner-documents.php'),
        ], 'afterburner-documents-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'afterburner-documents-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/afterburner-documents'),
        ], 'afterburner-documents-assets');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'afterburner-documents');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Ensure R2 disk is registered (fallback in case config wasn't ready in register())
        if (!$this->app['config']->get('filesystems.disks.r2')) {
            $this->registerR2Disk();
        }

        // Register policies
        $this->registerPolicies();

        // Register Livewire components
        $this->registerLivewireComponents();

        // Register navigation menu item
        $this->registerNavigation();
    }

    /**
     * Register Cloudflare R2 filesystem disk.
     */
    protected function registerR2Disk(): void
    {
        $r2Config = config('afterburner-documents.r2');

        // Only register if R2 is configured
        if (empty($r2Config['bucket']) || empty($r2Config['access_key_id']) || empty($r2Config['secret_access_key'])) {
            return;
        }

        // Check if AWS S3 Flysystem adapter is installed
        if (!class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
            \Log::warning('Cloudflare R2 disk not registered: league/flysystem-aws-s3-v3 package is required. Install it with: composer require league/flysystem-aws-s3-v3');
            return;
        }

        // Set the disk configuration using the config repository
        $this->app['config']->set('filesystems.disks.r2', [
            'driver' => 's3',
            'key' => $r2Config['access_key_id'],
            'secret' => $r2Config['secret_access_key'],
            'region' => $r2Config['region'] ?? 'auto',
            'bucket' => $r2Config['bucket'],
            'url' => $r2Config['url'] ?? null,
            'endpoint' => $r2Config['endpoint'],
            'use_path_style_endpoint' => $r2Config['use_path_style_endpoint'] ?? false,
            'throw' => false,
        ]);
    }

    /**
     * Register the application's policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(\Afterburner\Documents\Models\Document::class, \Afterburner\Documents\Policies\DocumentPolicy::class);
        Gate::policy(\Afterburner\Documents\Models\Folder::class, \Afterburner\Documents\Policies\FolderPolicy::class);
    }

    /**
     * Register Livewire components.
     */
    protected function registerLivewireComponents(): void
    {
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('documents.document-manager', \Afterburner\Documents\Livewire\Documents\DocumentManager::class);
            \Livewire\Livewire::component('documents.show', \Afterburner\Documents\Livewire\Documents\Show::class);
        }
    }

    /**
     * Register navigation menu item.
     */
    protected function registerNavigation(): void
    {
        if (!class_exists(\App\Support\Navigation::class)) {
            return;
        }

        \App\Support\Navigation::register([
            'label' => 'Documents',
            'route' => 'documents.index',
            'route_params' => function () {
                return ['team' => auth()->user()?->currentTeam?->id];
            },
            'icon' => 'document-text',
            'order' => 20,
            'permission' => function ($user) {
                if (!$user || !$user->currentTeam) {
                    return false;
                }
                // Check if documents feature is enabled
                if (!config('afterburner-documents.enabled', true)) {
                    return false;
                }
                // User must be a member of a team to see documents
                return true;
            },
            'active' => function () {
                return request()->routeIs('documents.*') || 
                       request()->routeIs('folders.*');
            },
        ]);
    }
}

