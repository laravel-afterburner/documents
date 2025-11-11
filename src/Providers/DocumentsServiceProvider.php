<?php

namespace Afterburner\Documents\Providers;

use Afterburner\Documents\Livewire\Documents\DocumentViewer;
use Afterburner\Documents\Livewire\Documents\Index;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
            __DIR__.'/../../config/afterburner-documents.php',
            'afterburner-documents'
        );
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

        // Register R2 disk configuration programmatically
        $this->registerR2Disk();

        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/afterburner-documents.php' => config_path('afterburner-documents.php'),
        ], 'afterburner-documents-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'afterburner-documents-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/afterburner-documents'),
        ], 'afterburner-documents-assets');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'afterburner-documents');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Register Livewire components
        $this->registerLivewireComponents();

        // Register policies
        $this->registerPolicies();

        // Register navigation menu item
        $this->registerNavigation();

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Afterburner\Documents\Console\Commands\InstallCommand::class,
            ]);
        }
    }

    /**
     * Register the R2 disk configuration programmatically.
     * This reads the disk configuration from config/afterburner-documents.php
     * and registers it in Laravel's filesystem configuration.
     */
    protected function registerR2Disk(): void
    {
        // Only register if not already configured in filesystems.php
        $existingDisks = config('filesystems.disks', []);
        
        if (!isset($existingDisks['r2'])) {
            $diskConfig = config('afterburner-documents.filesystem_disk', []);
            
            if (!empty($diskConfig)) {
                config([
                    'filesystems.disks.r2' => $diskConfig,
                ]);
            }
        }
    }

    /**
     * Register policies.
     */
    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \Afterburner\Documents\Models\Document::class,
            \Afterburner\Documents\Policies\DocumentPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \Afterburner\Documents\Models\Folder::class,
            \Afterburner\Documents\Policies\FolderPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \Afterburner\Documents\Models\RetentionTag::class,
            \Afterburner\Documents\Policies\RetentionTagPolicy::class
        );
    }

    /**
     * Register Livewire components.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('documents.index', Index::class);
        Livewire::component('documents.document-viewer', DocumentViewer::class);
    }

    /**
     * Register navigation menu item.
     */
    protected function registerNavigation(): void
    {
        // Check if Navigation class exists (from main afterburner project)
        if (!class_exists(\App\Support\Navigation::class)) {
            return;
        }

        \App\Support\Navigation::register([
            'label' => 'Documents',
            'route' => 'teams.documents.index',
            'route_params' => function () {
                $user = auth()->user();
                if (!$user || !$user->currentTeam) {
                    return [];
                }
                return ['team' => $user->currentTeam->id];
            },
            'icon' => 'document-text',
            'order' => 20,
            'permission' => function ($user) {
                if (!$user || !$user->currentTeam) {
                    return false;
                }
                return $user->can('viewAny', \Afterburner\Documents\Models\Document::class);
            },
            'active' => function () {
                return request()->routeIs('teams.documents.*');
            },
        ]);
    }
}

