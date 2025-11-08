# Afterburner Package Architecture & Implementation Plan

**Date:** 2025-11-05 18:41 PST  
**Purpose:** Extract multi-tenancy application into reusable Laravel packages following Laravel best practices  
**Organization:** laravel-afterburner (GitHub)

---

## Executive Summary

This document outlines the complete architecture for extracting the current Afterburner application into a modular Laravel package ecosystem. The approach follows **Laravel conventions and best practices** throughout, ensuring compatibility with Laravel framework updates and standard development workflows.

**Key Principle:** Core template has **zero** add-on dependencies. Add-on packages extend the template through Laravel's standard extension mechanisms (service providers, traits, config merging).

---

## Architecture Overview: Following "The Laravel Way"

This architecture mirrors Laravel's own structure:
- **Laravel Framework** → **Afterburner Template** (`laravel-afterburner/jetstream`)
- **Laravel Installer** (`laravel/installer`) → **Afterburner Installer** (`laravel-afterburner/installer`)
- **Laravel Packages** (Cashier, Sanctum) → **Afterburner Add-ons** (Subscriptions, Documents, etc.)

### Three-Tier Architecture

1. **Project Template** (`laravel-afterburner/jetstream`)
   - Type: `project` (like Laravel's skeleton)
   - Full Laravel application with Afterburner baseline pre-configured
   - Uses `App\` namespaces (standard Laravel structure)
   - Created via: `composer create-project laravel-afterburner/jetstream myapp`

2. **Standalone Installer** (`laravel-afterburner/installer`)
   - Type: `library` (CLI tool)
   - Globally installable installer with interactive add-on selection
   - Uses `composer create-project` internally
   - Installed via: `composer global require laravel-afterburner/installer`
   - Command: `afterburner new myapp`

3. **Add-On Packages** (`laravel-afterburner/subscriptions`, etc.)
   - Type: `library` (standard Laravel packages)
   - Optional modules that extend the template
   - Uses `Afterburner\[Module]\` namespaces
   - Installed via: `composer require laravel-afterburner/[module]`

---

## Part 1: Project Template (`laravel-afterburner/jetstream`)

### Repository
`github.com/laravel-afterburner/jetstream`

### Package Type
`"type": "project"` - This is a **full Laravel application template**, not a library package.

### Purpose
Acts as the canonical Laravel application template for building new Afterburner-based apps. When users run `composer create-project laravel-afterburner/jetstream myapp`, they get a complete, production-ready Laravel application with all Afterburner features pre-configured.

### Directory Structure

```markdown:/Users/andrewfox/WebApps/afterburner/.cursor/AFTERBURNER_PACKAGE_PLAN.md
<code_block_to_apply_changes_from>
laravel-afterburner/jetstream/
├── app/
│   ├── Actions/
│   │   ├── Afterburner/
│   │   └── Fortify/
│   ├── Console/
│   │   └── Commands/
│   │       ├── InstallCommand.php      # Install add-ons into existing projects
│   │       ├── PublishCommand.php      # Publish Afterburner assets
│   │       ├── EnablePersonalTeams.php
│   │       └── DisablePersonalTeams.php
│   ├── Events/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Livewire/
│   ├── Mail/
│   ├── Models/
│   │   ├── Team.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── TeamInvitation.php
│   │   ├── Membership.php
│   │   ├── User.php                    # Full User model (not a trait)
│   │   └── FeatureFlag.php
│   ├── Notifications/
│   ├── Observers/
│   ├── Policies/
│   ├── Providers/
│   │   ├── AfterburnerServiceProvider.php
│   │   ├── AppServiceProvider.php
│   │   ├── AuditServiceProvider.php
│   │   └── FortifyServiceProvider.php
│   ├── Services/
│   ├── Support/
│   │   ├── Afterburner.php
│   │   ├── Agent.php
│   │   ├── Features.php
│   │   ├── OwnerRole.php
│   │   └── Role.php
│   ├── Traits/
│   │   ├── HasRoles.php                # Renamed to HasAfterburnerRoles for clarity
│   │   ├── HasTeams.php
│   │   ├── HasPermissions.php
│   │   └── ...
│   └── View/
├── bootstrap/
├── config/
│   ├── afterburner.php
│   └── ...
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── web.php
│   └── api.php
├── stubs/
│   └── .env.example                    # Afterburner env vars template
├── composer.json
├── README.md
└── LICENSE
```

### Namespace Strategy: `App\`

**Important:** The template uses `App\` namespaces, not `Afterburner\Jetstream\`. This follows Laravel's convention for project templates (like Laravel's own skeleton). The template is a complete Laravel application, not a package.

- `App\Models\Team`
- `App\Actions\Afterburner\CreateTeam`
- `App\Traits\HasTeams`
- `App\Providers\AfterburnerServiceProvider`

### Template `composer.json`

```json
{
    "name": "laravel-afterburner/jetstream",
    "description": "Afterburner application template - Laravel starter with teams, roles, and permissions",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/fortify": "^1.23",
        "laravel/sanctum": "^4.0",
        "livewire/livewire": "^3.5"
    },
    "require-dev": {
        "laravel/pint": "^1.13",
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "scripts": {
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ]
    }
}
```

**Note:** No `laravel/jetstream` dependency - Jetstream has been fully removed and vendorized into the template.

### Core Features (Always Included)

These are **built in permanently** — never optional:
- **Livewire 3** as the UI stack (no Inertia)
- **Teams** (owner/member roles, invitations, switching, etc.)
- **Fortify authentication** (login, register, password reset)
- **Email verification**
- **Two-factor authentication (2FA)**
- **Profile management** (name, password, session management)
- **API tokens** via **Sanctum**
- **Custom roles and permissions system**
- **Feature flags** (runtime feature toggles)
- **System admin functionality** (optional, configurable)
- **Team invitations**
- **Tailwind + Vite + Alpine.js** front-end stack

### Jetstream Status

- Jetstream has been **fully removed** as a dependency
- The template **vendors and namespaces** all necessary Jetstream Livewire features directly into the project
- All code that previously referenced `Laravel\Jetstream\` now references `App\` namespaced equivalents
- The project retains the same UX and structure as Jetstream's Livewire stack but is self-contained

### Configuration (`config/afterburner.php`)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Entity Label
    |--------------------------------------------------------------------------
    |
    | The label used throughout the UI to refer to teams/organizations.
    | Examples: "team", "strata", "company", "organization"
    |
    */
    'entity_label' => env('AFTERBURNER_ENTITY_LABEL', 'organization'),

    /*
    |--------------------------------------------------------------------------
    | Create Team on Registration
    |--------------------------------------------------------------------------
    |
    | Whether to automatically create a team when a user registers.
    |
    */
    'create_team_on_registration' => env('AFTERBURNER_CREATE_TEAM_ON_REGISTRATION', true),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to routes registered by Afterburner.
    |
    */
    'middleware' => ['web'],

    'auth_session' => \Illuminate\Session\Middleware\AuthenticateSession::class,

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | Authentication guard used by Afterburner.
    |
    */
    'guard' => env('AFTERBURNER_GUARD', 'sanctum'),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features.
    |
    */
    'features' => [
        'team_invitations' => env('AFTERBURNER_TEAM_INVITATIONS', true),
        'impersonation' => env('AFTERBURNER_IMPERSONATION', true),
        'notifications' => env('AFTERBURNER_NOTIFICATIONS', true),
        'personal_teams' => env('AFTERBURNER_PERSONAL_TEAMS', false),
        'feature_flags' => env('AFTERBURNER_FEATURE_FLAGS_ENABLED', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | System Admin
    |--------------------------------------------------------------------------
    |
    | Whether system admin functionality is enabled. System admins can
    | impersonate users for troubleshooting purposes.
    |
    */
    'system_admin' => env('AFTERBURNER_SYSTEM_ADMIN_ENABLED', true),
];
```

### Default Roles System

The template includes a default role system that automatically assigns roles to users when they join teams.

#### How Default Roles Work

1. **Identification**: Default roles are identified by the `is_default` boolean flag in the `roles` table
2. **Automatic Assignment**: The default role is automatically assigned when:
   - A user registers and a team is created for them
   - A user is added to a team via `AddTeamMember` action
   - A user accepts a team invitation via `AcceptTeamInvitation` action
   - A user creates a new team (the creator receives the default role)

3. **Role Assignment Strategy**:
   - Users **always** receive the default role first (if it exists)
   - Additional roles can then be assigned on top of the default role
   - Users can have multiple roles simultaneously within a team
   - If no default role exists (`is_default => true`), users join teams without roles and must be assigned manually

#### Implementation Requirements

**Important:** The template does NOT automatically seed roles. Applications must:

1. **Create a Database Seeder** (e.g., `RolesSeeder`):
   ```php
   // database/seeders/RolesSeeder.php
   public function run(): void
   {
       Role::create([
           'name' => 'Member',
           'slug' => 'member',
           'description' => 'Default team member role',
           'is_default' => true,  // Mark as default
           'hierarchy' => 100,
           'badge_color' => 'gray',
           'max_members' => null,  // Unlimited
       ]);
       
       // Add other roles...
   }
   ```

2. **Run the Seeder**:
   ```bash
   php artisan db:seed --class=RolesSeeder
   ```

#### Best Practices

- **One Default Role**: Only one role should have `is_default => true` at any given time
- **Low Permissions**: The default role should typically have minimal permissions (e.g., basic viewing)
- **High Hierarchy Value**: Set a high hierarchy value (e.g., 100) so default roles have lower priority than administrative roles
- **Unlimited Members**: Set `max_members => null` for the default role to allow unlimited assignments

### Support Classes

The template includes several utility classes in the `App\Support` directory:

#### `Support/Agent.php`
- **Purpose:** User agent detection utility
- **Description:** Extends `Detection\MobileDetect` to provide enhanced browser and platform detection capabilities
- **Methods:**
  - `platform()` - Get the platform name from User Agent
  - `browser()` - Get the browser name from User Agent
  - `isDesktop()` - Determine if the device is a desktop computer

#### `Support/OwnerRole.php`
- **Purpose:** Helper class representing the owner role
- **Description:** Extends `Support/Role.php` to provide a pre-configured owner role instance with all permissions (`['*']`)
- **Properties:**
  - `key`: 'owner'
  - `name`: 'Owner'
  - `permissions`: ['*'] (all permissions)

#### `Support/Role.php`
- **Purpose:** Support class for role definitions (not to be confused with `Models/Role.php`)
- **Description:** A simple data transfer object (DTO) class that implements `JsonSerializable` for role definitions
- **Properties:**
  - `key` - The role identifier
  - `name` - The display name
  - `permissions` - Array of permission slugs
  - `description` - Optional description

#### `Support/Afterburner.php`
- **Purpose:** Facade/facade-like class for Afterburner functionality
- **Description:** Provides static methods for accessing models, checking features, and configuring behavior
- **Key Methods:**
  - `userModel()`, `teamModel()`, `membershipModel()`, `teamInvitationModel()` - Get model class names
  - `newUserModel()`, `newTeamModel()` - Create new model instances
  - `hasTeamFeatures()`, `hasApiFeatures()`, etc. - Feature checks

#### `Support/Features.php`
- **Purpose:** Feature flag management
- **Description:** Provides methods for checking if features are enabled. Uses a hybrid approach: checks database (`feature_flags` table) for runtime overrides first, then falls back to config file for deployment-time defaults
- **Key Methods:**
  - `enabled($feature)` - Check if a feature is enabled
  - `managesProfilePhotos()`, `hasTeamFeatures()`, `hasPersonalTeams()`, etc. - Feature-specific checks

### User Model Integration

**Important:** The template includes a **full User model** in `app/Models/User.php` with all necessary traits integrated. This follows Laravel's convention for project templates - users get a complete, working application out of the box.

The User model includes traits like:
- `HasRoles` (renamed to `HasAfterburnerRoles` for clarity)
- `HasTeams`
- `HasPermissions`
- `HasProfilePhoto`
- `HasApiTokens`
- `TwoFactorAuthenticatable`

### Artisan Commands (In Template)

The template provides Artisan commands for managing add-ons in existing projects:

- `afterburner:install` - Install add-ons into an existing project
  - Merges Afterburner environment variables into `.env.example`
  - Publishes config, migrations, and views
  - Runs migrations
  
- `afterburner:publish` - Publish all Afterburner assets (config, migrations, views)
  - Does NOT modify `.env.example` (use `afterburner:install` for that)
  
- `afterburner:enable-personal-teams` - Enable personal teams feature
- `afterburner:disable-personal-teams` - Disable personal teams feature

### Environment Variables Template

The template includes a `stubs/.env.example` file containing all Afterburner-specific environment variables with documentation:

```env
# ============================================================================
# Afterburner Configuration
# ============================================================================
# These environment variables are automatically added to your .env.example
# when you create a new Afterburner project.
# ============================================================================

# Entity Label
# The label used throughout the UI to refer to teams/organizations.
AFTERBURNER_ENTITY_LABEL=organization

# Authentication Guard
# The authentication guard to use for Afterburner routes.
AFTERBURNER_GUARD=sanctum

# Profile Photo Disk
# The filesystem disk to use for storing user profile photos.
AFTERBURNER_PROFILE_PHOTO_DISK=public

# Feature Flags
AFTERBURNER_TEAM_INVITATIONS=true
AFTERBURNER_IMPERSONATION=true
AFTERBURNER_NOTIFICATIONS=true
AFTERBURNER_PERSONAL_TEAMS=false
AFTERBURNER_FEATURE_FLAGS_ENABLED=true
AFTERBURNER_SYSTEM_ADMIN_ENABLED=true
```

### Maintenance Policy

- This template defines the **Afterburner Core Baseline**
- It remains pinned to the Laravel version it's built on (e.g. Laravel 11)
- Updates to newer Laravel releases will be done manually when desired — not automatically
- Future Laravel changes will not affect existing projects unless explicitly merged

### Distribution

- Published on **Packagist** as `laravel-afterburner/jetstream`
- Marked with `"type": "project"` so it behaves like a Laravel starter (not a library)
- Usage:
  ```bash
  composer create-project laravel-afterburner/jetstream myapp
  ```

---

## Part 2: Standalone Installer (`laravel-afterburner/installer`)

### Repository
`github.com/laravel-afterburner/installer`

### Package Type
`"type": "library"` - This is a CLI tool package

### Purpose
Provides a globally installable installer command (following Laravel's installer pattern) that creates new projects with interactive add-on selection.

### Installation

```bash
# Install globally
composer global require laravel-afterburner/installer

# Ensure Composer's global bin directory is in your PATH
# macOS: ~/.composer/vendor/bin or ~/.config/composer/vendor/bin
# Linux: ~/.config/composer/vendor/bin or ~/.composer/vendor/bin
# Windows: %USERPROFILE%\AppData\Roaming\Composer\vendor\bin

# Use from anywhere
afterburner new myapp
```

### Directory Structure

```
laravel-afterburner/installer/
├── src/
│   ├── Console/
│   │   └── Commands/
│   │       └── NewCommand.php          # Main installer command
│   ├── Installers/
│   │   ├── CoreInstaller.php           # Handles core template setup
│   │   └── PackageInstallerInterface.php  # Contract for add-on installers
│   └── InstallerServiceProvider.php
├── composer.json
├── README.md
└── LICENSE
```

### Installer `composer.json`

```json
{
    "name": "laravel-afterburner/installer",
    "description": "Afterburner project installer - Create new Laravel projects with Afterburner",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "symfony/console": "^6.0",
        "symfony/process": "^6.0"
    },
    "bin": [
        "afterburner"
    ],
    "autoload": {
        "psr-4": {
            "Afterburner\\Installer\\": "src/"
        }
    }
}
```

### Installation Flow

When users run `afterburner new myapp`, the installer:

1. **Creates Laravel project**: `composer create-project laravel-afterburner/jetstream myapp`
2. **Interactive add-on selection**: Shows checkboxes for available add-ons:
   - [ ] Subscriptions
   - [ ] Documents (future)
   - [ ] Communications (future)
   - [ ] Voting (future)
   - [ ] Meetings (future)
3. **Installs selected add-ons**:
   - Runs `composer require laravel-afterburner/[package]` for each selected package
   - Runs `php artisan vendor:publish --tag=afterburner-[package]-config --tag=afterburner-[package]-migrations`
   - Merges environment variables into `.env.example`
   - Runs package install hooks if needed
4. **Finalizes**:
   - Runs migrations
   - Displays next steps and configuration instructions

### Checkbox Selection UI

- Uses Symfony Console Question Helper
- Space toggles selection
- Arrow keys navigate
- Enter confirms
- Shows [X] for selected, [ ] for unselected
- Displays short description for each package

### Package Registry

The installer maintains a registry of available packages with:
- Package name (composer name)
- Description
- Availability status
- Dependencies

---

## Part 3: Add-On Packages

### Architecture Philosophy

Each add-on package follows Laravel's standard package structure and conventions:
- **Separate repositories** under `laravel-afterburner` organization
- **Independent versioning** - Each package can be updated independently
- **Clean dependencies** - Only requires `laravel-afterburner/jetstream` (the template)
- **Standard Laravel package structure** - Service providers, config files, migrations, views

### Why Separate Packages?

1. **Laravel Pattern Compliance** - Follows established patterns (Cashier, Sanctum, etc.)
2. **Clean Dependencies** - Template stays lightweight
3. **Flexible Installation** - Use template for personal apps, add features as needed
4. **Independent Versioning** - Update add-ons without touching template
5. **Clear Boundaries** - Easier to maintain and reason about

### Add-On Package Structure

Each add-on follows this pattern:

```
laravel-afterburner-[module]/
├── src/
│   ├── Models/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Livewire/
│   ├── Actions/
│   ├── Notifications/
│   ├── Providers/
│   │   └── [Module]ServiceProvider.php
│   ├── Console/
│   │   └── Commands/
│   │       └── InstallCommand.php
│   └── Concerns/
│       └── Has[Module].php          # Trait for extending models
├── database/
│   └── migrations/
├── config/
│   └── afterburner-[module].php
├── resources/
│   └── views/
├── routes/
│   └── web.php
├── composer.json
└── README.md
```

### Namespace Strategy: `Afterburner\[Module]\`

**Important:** Add-on packages use `Afterburner\[Module]\` namespaces (e.g., `Afterburner\Subscriptions\`). This follows Laravel's convention for vendor packages.

### Common Add-On Package `composer.json` Pattern

```json
{
    "name": "laravel-afterburner/[module]",
    "description": "[Module description] for Afterburner applications",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel-afterburner/jetstream": "^1.0"
    },
    "require-dev": {
        "laravel/pint": "^1.13",
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Afterburner\\[Module]\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Afterburner\\[Module]\\Providers\\[Module]ServiceProvider"
            ],
            "commands": [
                "Afterburner\\[Module]\\Console\\Commands\\InstallCommand"
            ]
        }
    }
}
```

### Add-On Configuration Pattern

Each add-on has its own config file (`config/afterburner-[module].php`):

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | [Module] Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for [module] functionality.
    |
    */
    'enabled' => env('AFTERBURNER_[MODULE]_ENABLED', true),

    // ... module-specific configuration
];
```

### Add-On Service Provider Pattern

```php
<?php

namespace Afterburner\[Module]\Providers;

use Illuminate\Support\ServiceProvider;

class [Module]ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Check if template is installed (optional safety check)
        if (!class_exists(\App\Models\Team::class)) {
            return;
        }

        $this->mergeConfigFrom(
            __DIR__.'/../../config/afterburner-[module].php',
            'afterburner-[module]'
        );
    }

    public function boot(): void
    {
        // Check if template is installed
        if (!class_exists(\App\Models\Team::class)) {
            return;
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/afterburner-[module].php' => config_path('afterburner-[module].php'),
        ], 'afterburner-[module]-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'afterburner-[module]-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/afterburner-[module]'),
        ], 'afterburner-[module]-assets');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Register Livewire components
        $this->registerLivewireComponents();

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\InstallCommand::class,
            ]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        // Register Livewire components...
    }
}
```

### Publish Tags

Each add-on uses standardized publish tags:
- `afterburner-[module]-config` - Configuration files
- `afterburner-[module]-migrations` - Database migrations
- `afterburner-[module]-assets` - Views, CSS, JS, etc.

### Environment Variables

All add-on configuration uses the `AFTERBURNER_[MODULE]_*` prefix:
- `AFTERBURNER_SUBSCRIPTIONS_ENABLED`
- `AFTERBURNER_DOCUMENTS_*`
- `AFTERBURNER_COMMUNICATIONS_*`
- etc.

---

## Add-On Package: Subscriptions (`laravel-afterburner/subscriptions`)

### Package Name
`laravel-afterburner/subscriptions`

### Repository
`github.com/laravel-afterburner/subscriptions`

### Namespace
`Afterburner\Subscriptions\`

### Directory Structure

```
laravel-afterburner-subscriptions/
├── src/
│   ├── Models/
│   │   └── SubscriptionPlan.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Admin/
│   │   │       └── SubscriptionPlanController.php
│   │   └── Controllers/
│   │       └── WebhookController.php
│   ├── Livewire/
│   │   ├── Admin/
│   │   │   ├── SubscriptionPlans/
│   │   │   │   ├── Index.php
│   │   │   │   ├── Create.php
│   │   │   │   └── Edit.php
│   │   │   └── SubscriptionSettings.php
│   │   └── Teams/
│   │       └── SubscriptionManager.php
│   ├── Actions/
│   │   └── Stripe/
│   │       ├── CreateSubscription.php
│   │       ├── SyncStripeProducts.php
│   │       └── HandleWebhookEvent.php
│   ├── Notifications/
│   │   └── BillingContactChanged.php
│   ├── Middleware/
│   │   └── EnsureSubscriptionActive.php
│   ├── Providers/
│   │   └── SubscriptionsServiceProvider.php
│   ├── Concerns/
│   │   └── HasSubscriptions.php        # Trait for Team model
│   └── Console/
│       └── Commands/
│           └── InstallCommand.php
├── database/
│   └── migrations/
│       ├── create_subscription_plans_table.php
│       ├── add_stripe_fields_to_teams_table.php
│       ├── add_billing_contact_to_teams_table.php
│       └── create_team_subscriptions_table.php
├── config/
│   └── afterburner-subscriptions.php
├── resources/
│   └── views/
│       ├── admin/
│       │   └── subscription-plans/
│       └── teams/
│           └── subscription-manager.blade.php
├── routes/
│   └── web.php
├── composer.json
└── README.md
```

### Subscription Package `composer.json`

```json
{
    "name": "laravel-afterburner/subscriptions",
    "description": "Stripe subscription management for Afterburner applications",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel-afterburner/jetstream": "^1.0",
        "laravel/cashier": "^15.0",
        "livewire/livewire": "^3.6"
    },
    "require-dev": {
        "laravel/pint": "^1.13",
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Afterburner\\Subscriptions\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Afterburner\\Subscriptions\\Providers\\SubscriptionsServiceProvider"
            ],
            "commands": [
                "Afterburner\\Subscriptions\\Console\\Commands\\InstallCommand"
            ]
        }
    }
}
```

### Subscription Configuration

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscriptions Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for subscription functionality.
    |
    */
    'enabled' => env('AFTERBURNER_SUBSCRIPTIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Stripe API keys and webhook settings.
    |
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for subscriptions.
    |
    */
    'currency' => env('AFTERBURNER_SUBSCRIPTIONS_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Default Trial Days
    |--------------------------------------------------------------------------
    |
    | Default trial period for new teams (when not specified by plan).
    |
    */
    'default_trial_days' => env('AFTERBURNER_SUBSCRIPTIONS_DEFAULT_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Subscription Plan Features Template
    |--------------------------------------------------------------------------
    |
    | Structure for plan features that can be customized per application.
    | This is just a template - actual features are stored in database.
    |
    */
    'plan_features_template' => [
        'max_teams' => null, // null = unlimited
        'max_users_per_team' => null,
        'max_storage_gb' => null,
        'features' => [], // Custom feature list
    ],
];
```

### Extension Trait: `HasSubscriptions`

```php
<?php

namespace Afterburner\Subscriptions\Concerns;

use Afterburner\Subscriptions\Models\SubscriptionPlan;

trait HasSubscriptions
{
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function hasActiveSubscription(): bool
    {
        if (!$this->subscriptionPlan) {
            return false;
        }

        // Check if on trial
        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return true;
        }

        // Check Stripe subscription status (requires Cashier)
        if (method_exists($this, 'subscription')) {
            return $this->subscription() && $this->subscription()->active();
        }

        return false;
    }

    public function assignPlan(SubscriptionPlan $plan): void
    {
        $this->update([
            'subscription_plan_id' => $plan->id,
            'trial_ends_at' => $plan->trial_days > 0
                ? now()->addDays($plan->trial_days)
                : null,
        ]);
    }
}
```

### Artisan Command: `afterburner:subscriptions:install`

Installs the subscriptions package into an existing project:
- Merges environment variables into `.env.example`
- Publishes config, migrations, and views
- Runs migrations
- Displays next steps

---

## Future Add-On Packages

### Documents Package
- **Name:** `laravel-afterburner/documents`
- **Namespace:** `Afterburner\Documents\`
- **Repository:** `github.com/laravel-afterburner/documents`
- **Status:** Future development

### Communications Package
- **Name:** `laravel-afterburner/communications`
- **Namespace:** `Afterburner\Communications\`
- **Repository:** `github.com/laravel-afterburner/communications`
- **Status:** Future development

### Voting Package
- **Name:** `laravel-afterburner/voting`
- **Namespace:** `Afterburner\Voting\`
- **Repository:** `github.com/laravel-afterburner/voting`
- **Status:** Future development

### Meetings Package
- **Name:** `laravel-afterburner/meetings`
- **Namespace:** `Afterburner\Meetings\`
- **Repository:** `github.com/laravel-afterburner/meetings`
- **Status:** Future development

Each future add-on follows the same pattern as subscriptions:
- Requires `laravel-afterburner/jetstream`
- Has its own service provider
- Has its own config file (`afterburner-[module].php`)
- Uses publish tags: `afterburner-[module]-*`
- Uses env vars: `AFTERBURNER_[MODULE]_*`

---

## Environment Variables Reference

All configuration uses the `AFTERBURNER_*` prefix:

### Template (Core)
- `AFTERBURNER_ENTITY_LABEL` - Entity label (default: organization)
- `AFTERBURNER_GUARD` - Authentication guard (default: sanctum)
- `AFTERBURNER_CREATE_TEAM_ON_REGISTRATION` - Auto-create team on registration (default: true)
- `AFTERBURNER_TEAM_INVITATIONS` - Enable team invitations (default: true)
- `AFTERBURNER_IMPERSONATION` - Enable impersonation (default: true)
- `AFTERBURNER_NOTIFICATIONS` - Enable notifications (default: true)
- `AFTERBURNER_PERSONAL_TEAMS` - Enable personal teams feature (default: false)
- `AFTERBURNER_FEATURE_FLAGS_ENABLED` - Enable runtime feature toggles (default: true)
- `AFTERBURNER_SYSTEM_ADMIN_ENABLED` - Enable system admin functionality (default: true)
- `AFTERBURNER_PROFILE_PHOTO_DISK` - Profile photo storage disk (default: public)

### Subscriptions Package
- `AFTERBURNER_SUBSCRIPTIONS_ENABLED` - Enable subscriptions
- `AFTERBURNER_SUBSCRIPTIONS_CURRENCY` - Currency (default: usd)
- `AFTERBURNER_SUBSCRIPTIONS_DEFAULT_TRIAL_DAYS` - Default trial days

### Future Add-Ons
- `AFTERBURNER_DOCUMENTS_*`
- `AFTERBURNER_COMMUNICATIONS_*`
- `AFTERBURNER_VOTING_*`
- `AFTERBURNER_MEETINGS_*`

---

## Installation Scenarios

### Scenario 1: New Project (Using Installer)

```bash
# Install installer globally
composer global require laravel-afterburner/installer

# Create new project with interactive add-on selection
afterburner new my-project

# Follow interactive prompts to select add-ons
# Project is ready to use
```

### Scenario 2: New Project (Direct Composer)

```bash
# Create new project directly
composer create-project laravel-afterburner/jetstream my-project

# Install add-ons manually
composer require laravel-afterburner/subscriptions
php artisan afterburner:subscriptions:install
```

### Scenario 3: Existing Laravel Project

```bash
# Install core template features (not applicable - template is full Laravel app)
# Instead, install add-ons into existing Laravel project

composer require laravel-afterburner/subscriptions
php artisan afterburner:subscriptions:install
```

### Scenario 4: Personal App (No Subscriptions)

```bash
# Create new project
composer create-project laravel-afterburner/jetstream my-project

# Configure
# Edit config/afterburner.php
# No need to install subscription package
```

---

## Implementation Steps

### Phase 1: Extract Template

1. Create template repository on GitHub: `laravel-afterburner/jetstream`
2. Move current application code to template repository
3. Update `composer.json`:
   - Change name to `laravel-afterburner/jetstream`
   - Set type to `"project"`
   - Remove `laravel/jetstream` dependency
   - Keep `App\` namespaces
4. Update all references from Jetstream to Afterburner
5. Create `.env.example` stub with Afterburner variables
6. Add Artisan commands for add-on management
7. Write tests
8. Create documentation

### Phase 2: Build Standalone Installer

1. Create installer repository: `laravel-afterburner/installer`
2. Build CLI tool following Laravel installer pattern
3. Implement interactive checkbox selection
4. Implement package registry
5. Test on Linux, macOS, Windows
6. Create documentation

### Phase 3: Build Subscription Package

1. Create subscription package repository: `laravel-afterburner/subscriptions`
2. Extract subscription code from current application
3. Update namespaces to `Afterburner\Subscriptions\`
4. Create service provider
5. Create install command
6. Implement Stripe integration
7. Write tests
8. Create documentation

### Phase 4: Update Current Application

1. Remove extracted code from application
2. Update application to use template structure
3. Install add-ons via composer
4. Update configuration
5. Run migrations
6. Test thoroughly

---

## Testing Strategy

### Template Tests
- Test team creation/management
- Test role/permission system
- Test User model functionality
- Test Livewire components
- Test Artisan commands
- Test feature flags

### Installer Tests
- Test `afterburner new` creates valid Laravel project
- Test checkbox UI
- Test install of core + multiple add-ons
- Test error handling (bad names, Composer failures)
- Test on Linux, macOS, Windows

### Add-On Package Tests
- Test subscription plan management
- Test Stripe integration
- Test webhook handling
- Test subscription assignment to teams

---

## Versioning Strategy

### Template
- Start at `1.0.0`
- Use semantic versioning
- Breaking changes → MAJOR version bump
- Pinned to Laravel version (e.g., Laravel 11)

### Add-On Packages
- Each starts at `1.0.0`
- Must be compatible with template version `^1.0`
- Independent versioning

### Compatibility Matrix

| Subscription | Documents | Communications | Voting | Meetings | Template |
|--------------|-----------|----------------|--------|----------|----------|
| ^1.0         | ^1.0      | ^1.0           | ^1.0   | ^1.0     | ^1.0     |

---

## Documentation Requirements

### Template README
- Installation via installer: `afterburner new myapp`
- Direct installation: `composer create-project laravel-afterburner/jetstream myapp`
- Configuration options
- User model guide
- Usage examples
- API documentation

### Installer README
- Installation: `composer global require laravel-afterburner/installer`
- Usage: `afterburner new my-project`
- Available packages and descriptions
- Installation flow
- Troubleshooting

### Add-On Package READMEs
- Installation instructions
- Setup guide
- Configuration options
- Usage examples

---

## Key Design Principles: "The Laravel Way"

1. **Follow Laravel Conventions**
   - Project templates use `App\` namespaces
   - Packages use vendor namespaces (`Afterburner\[Module]\`)
   - Service providers follow Laravel patterns
   - Publish tags follow Laravel conventions

2. **Separation of Concerns**
   - Template is complete Laravel application
   - Installer is separate CLI tool
   - Add-ons are independent packages

3. **Framework Compatibility**
   - Template pinned to Laravel version
   - Updates handled manually
   - No automatic breaking changes

4. **Flexibility**
   - Template usable standalone
   - Add-ons optional and modular
   - Easy to update individual components

5. **Standard Laravel Patterns**
   - Service provider auto-discovery
   - Config merging
   - Migration loading
   - View publishing
   - Artisan commands

---

## Next Steps

1. ✅ Review and approve this plan
2. Set up GitHub organization: `laravel-afterburner`
3. Create repository: `laravel-afterburner/jetstream`
4. Create repository: `laravel-afterburner/installer`
5. Begin Phase 1: Extract template
    5a. Update composer.json name and description
    5b. Rename HasRoles to HasAfterburnerRoles
    5c. Create placeholder InstallCommand and PublishCommand
6. Test template in isolation
7. Begin Phase 2: Build installer
8. Begin Phase 3: Build subscription package
9. Test all components together
10. Update current application to use template
11. Publish packages to Packagist (when ready)

---

**Last Updated:** 2025-11-05 18:41 PST  
**Status:** Planning Phase
```

This document consolidates the three files into a single plan that follows Laravel conventions. It clarifies:
- Template uses `App\` namespaces (like Laravel's skeleton)
- Add-ons use `Afterburner\[Module]\` namespaces (like Laravel packages)
- Standalone installer follows Laravel's installer pattern
- Separation between template, installer, and add-ons

Should I save this as a new file in the `.cursor` directory?
