<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\RetentionTag;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRetentionTag
{
    /**
     * Create a new retention tag.
     *
     * @param  int  $teamId
     * @param  string  $name
     * @param  int  $retentionPeriodDays
     * @param  string  $color
     * @param  string|null  $description
     * @param  User  $user
     * @return RetentionTag
     */
    public function execute(
        int $teamId,
        string $name,
        int $retentionPeriodDays,
        string $color,
        ?string $description,
        User $user
    ): RetentionTag {
        return DB::transaction(function () use ($teamId, $name, $retentionPeriodDays, $color, $description, $user) {
            // Check for duplicate tag name in same team
            $existing = RetentionTag::where('team_id', $teamId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                throw new \Exception("A retention tag with the name '{$name}' already exists for this team.");
            }

            // Create retention tag
            $tag = RetentionTag::create([
                'team_id' => $teamId,
                'name' => $name,
                'retention_period_days' => $retentionPeriodDays,
                'color' => $color,
                'description' => $description,
                'created_by' => $user->id,
            ]);

            // Create audit log entry
            AuditLog::create([
                'user_id' => $user->id,
                'action_type' => 'created',
                'category' => 'documents',
                'event_name' => 'retention_tag.created',
                'auditable_type' => RetentionTag::class,
                'auditable_id' => $tag->id,
                'team_id' => $teamId,
                'changes' => [
                    'name' => $name,
                    'retention_period_days' => $retentionPeriodDays,
                    'color' => $color,
                ],
            ]);

            return $tag;
        });
    }
}

