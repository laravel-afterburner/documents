<?php

namespace Afterburner\Documents\Providers;

use Afterburner\Documents\Console\Commands\CleanupUploadSessionsCommand;
use Afterburner\Documents\Console\Commands\InstallCommand;
use Afterburner\Documents\Database\Seeders\DocumentPermissionsSeeder;
use Afterburner\Documents\Livewire\Documents\DocumentViewer;
use Afterburner\Documents\Livewire\Documents\Index;
use Afterburner\Documents\Livewire\Settings\DocumentsSettings;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Models\RetentionTag;
use Afterburner\Documents\Policies\DocumentPolicy;
use Afterburner\Documents\Policies\FolderPolicy;
use Afterburner\Documents\Policies\RetentionTagPolicy;
use Afterburner\Playbook\Support\Playbook;
use App\Models\Team;
use App\Support\Navigation;
use App\Support\PackageSeederRegistry;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
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
        if (! class_exists(Team::class)) {
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
        if (! class_exists(Team::class)) {
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

        // Override Spatie FilePond upload view after all providers have booted
        $this->app->booted(function () {
            Blade::component('afterburner-documents::filepond.upload', 'filepond::upload');
        });

        // Register policies
        $this->registerPolicies();
        $this->registerSubscriptionGates();

        // Skip noisy upload chunk/init HTTP requests from audit logging
        $this->registerAuditSkipRoutes();

        // Register navigation menu item
        $this->registerNavigation();
        $this->registerPlaybook();

        // Register system settings section
        $this->registerSystemSettings();
        $this->registerPackageSeeder();

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                CleanupUploadSessionsCommand::class,
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

        if (isset($existingDisks['r2'])) {
            return;
        }

        $diskConfig = config('afterburner-documents.filesystem_disk', []);
        $bucket = $diskConfig['bucket'] ?? '';
        $key = $diskConfig['key'] ?? '';

        if (empty($bucket) || empty($key)) {
            config([
                'filesystems.disks.r2' => [
                    'driver' => 'local',
                    'root' => storage_path('app/documents'),
                    'throw' => false,
                ],
            ]);
        } elseif (! empty($diskConfig)) {
            config([
                'filesystems.disks.r2' => $diskConfig,
            ]);
        }

        config([
            'filesystems.disks.documents-uploads' => [
                'driver' => 'local',
                'root' => storage_path('app/documents/uploads'),
                'throw' => false,
            ],
        ]);
    }

    /**
     * Register policies.
     */
    protected function registerSubscriptionGates(): void
    {
        Gate::define('documents.access-team', function ($user, Team $team) {
            return app(DocumentPolicy::class)->access($user, $team);
        });
    }

    protected function registerPolicies(): void
    {
        Gate::policy(
            Document::class,
            DocumentPolicy::class
        );

        Gate::policy(
            Folder::class,
            FolderPolicy::class
        );

        Gate::policy(
            RetentionTag::class,
            RetentionTagPolicy::class
        );
    }

    /**
     * Register Livewire components.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('documents.index', Index::class);
        Livewire::component('documents.document-viewer', DocumentViewer::class);
        Livewire::component('documents.settings.documents-settings', DocumentsSettings::class);
    }

    protected function registerAuditSkipRoutes(): void
    {
        if (! config()->has('audit.skip_routes')) {
            return;
        }

        $skipRoutes = config('afterburner-documents.audit.skip_routes', []);

        config([
            'audit.skip_routes' => array_values(array_unique(array_merge(
                config('audit.skip_routes', []),
                $skipRoutes
            ))),
        ]);
    }

    /**
     * Register navigation menu item.
     */
    protected function registerNavigation(): void
    {
        // Check if Navigation class exists (from main afterburner project)
        if (! class_exists(Navigation::class)) {
            return;
        }

        Navigation::register([
            'label' => 'Documents',
            'route' => 'teams.documents.index',
            'route_params' => function () {
                $user = auth()->user();
                if (! $user || ! $user->currentTeam) {
                    return [];
                }

                return ['team' => $user->currentTeam->id];
            },
            'icon' => 'document-text',
            'order' => 30,
            'permission' => function ($user) {
                if (! $user || ! $user->currentTeam) {
                    return false;
                }

                return Gate::forUser($user)->check('documents.access-team', $user->currentTeam);
            },
            'active' => function () {
                return request()->routeIs('teams.documents.*');
            },
        ]);
    }

    protected function registerPlaybook(): void
    {
        if (! class_exists(Playbook::class)) {
            return;
        }

        Playbook::register([
            'key' => 'documents',
            'label' => 'Documents',
            'order' => 10,
            'path' => __DIR__.'/../../playbook',
            'enabled' => fn () => config('afterburner-documents.enabled', true),
            'permission' => fn ($user) => $user?->currentTeam
                && Gate::forUser($user)->check('documents.access-team', $user->currentTeam),
        ]);
    }

    protected function registerSystemSettings(): void
    {
        if (! class_exists(SystemSettings::class)) {
            return;
        }

        if (! config('afterburner-documents.enabled', true)) {
            return;
        }

        SystemSettings::register([
            'key' => 'documents',
            'order' => 10,
            'component' => 'documents.settings.documents-settings',
            'params' => fn ($team) => ['team' => $team],
            'permission' => function ($user) {
                if (! $user || ! $user->currentTeam) {
                    return false;
                }

                return $user->can('update', $user->currentTeam)
                    && Gate::forUser($user)->check('documents.access-team', $user->currentTeam);
            },
        ]);
    }

    protected function registerPackageSeeder(): void
    {
        if (class_exists(PackageSeederRegistry::class)) {
            PackageSeederRegistry::register(DocumentPermissionsSeeder::class);
        }
    }
}
