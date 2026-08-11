<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Models\User;

trait LogsActivity
{
    protected function logActivity(User $user, string $aktivitas): void
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'aktivitas' => $aktivitas,
        ]);
    }
}
