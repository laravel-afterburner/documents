<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\RetentionTag;
use Afterburner\Documents\Support\DocumentsAuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateRetentionTag
{
    /**
     * Update a retention tag.
     *
     * @param  RetentionTag  $tag
     * @param  array  $attributes
     * @param  User  $user
     * @return RetentionTag
     */
    public function execute(RetentionTag $tag, array $attributes, User $user): RetentionTag
    {
        return DB::transaction(function () use ($tag, $attributes, $user) {
            $oldAttributes = $tag->getAttributes();

            // Check for duplicate tag name in same team (if name is being changed)
            if (isset($attributes['name']) && $attributes['name'] !== $tag->name) {
                $existing = RetentionTag::where('team_id', $tag->team_id)
                    ->where('name', $attributes['name'])
                    ->where('id', '!=', $tag->id)
                    ->first();

                if ($existing) {
                    throw new \Exception("A retention tag with the name '{$attributes['name']}' already exists for this team.");
                }
            }

            // Update tag
            $tag->update($attributes);

            // Create audit log entry
            $changes = [];
            foreach ($attributes as $key => $value) {
                if (isset($oldAttributes[$key]) && $oldAttributes[$key] != $value) {
                    $changes[$key] = [
                        'before' => $oldAttributes[$key],
                        'after' => $value,
                    ];
                }
            }

            DocumentsAuditLogger::retentionTagUpdated($tag, $user, $changes);

            return $tag->fresh();
        });
    }
}

