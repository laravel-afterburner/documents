<?php

namespace Afterburner\Documents\Tests;

use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Providers\DocumentsServiceProvider;
use App\Models\SubscribableTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'afterburner-subscriptions.enabled' => false,
            'afterburner-documents.upload.max_file_size' => 10485760,
            'afterburner-documents.upload.chunk_size' => 1024,
            'afterburner-documents.upload.max_chunks' => 100,
            'afterburner-documents.upload.allowed_mime_types' => [
                'text/plain',
                'application/pdf',
            ],
            'queue.default' => 'sync',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            DocumentsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.guards.web.provider', 'users');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function createTeamWithUser(): array
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team = Team::query()->create([
            'name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $team->users()->attach($user);

        return [$user, $team];
    }

    protected function createTeamWithUserAndFolder(): array
    {
        [$user, $team] = $this->createTeamWithUser();

        $folder = Folder::query()->create([
            'team_id' => $team->id,
            'name' => 'Reports',
            'slug' => 'reports',
            'created_by' => $user->id,
        ]);

        return [$user, $team, $folder];
    }

    /**
     * @return array{0: User, 1: SubscribableTeam}
     */
    /**
     * @param  array<string, mixed>  $teamAttributes
     * @return array{0: User, 1: SubscribableTeam}
     */
    protected function createSubscribableTeamWithUser(array $teamAttributes = []): array
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'subscriber-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team = SubscribableTeam::query()->create(array_merge([
            'name' => 'Subscribable Team',
            'user_id' => $user->id,
            'documents_entitled' => true,
            'storage_within_limit' => true,
        ], $teamAttributes));

        $team->users()->attach($user);

        return [$user, $team];
    }
}
