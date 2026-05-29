<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Support\Collection;

class FolderPathFromCollectionTest extends TestCase
{
    public function test_path_from_collection_builds_nested_path(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $root = Folder::query()->create([
            'team_id' => $team->id,
            'name' => 'Reports',
            'slug' => 'reports',
            'created_by' => $user->id,
        ]);

        $child = Folder::query()->create([
            'team_id' => $team->id,
            'parent_id' => $root->id,
            'name' => '2026',
            'slug' => '2026',
            'created_by' => $user->id,
        ]);

        $folders = new Collection([$root, $child]);
        $path = Folder::pathFromCollection($folders, $child->id);

        $this->assertSame(['Reports', '2026'], collect($path)->pluck('name')->all());
    }

    public function test_path_from_collection_returns_empty_array_for_root_documents(): void
    {
        $this->assertSame([], Folder::pathFromCollection(new Collection, null));
    }
}
