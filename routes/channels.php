<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;


Broadcast::channel(
    'App.Models.User.{id}',
    function (User $user, int $id): bool {
        return (int) $user->id ===
            (int) $id;
    }
);


Broadcast::channel(
    'tenant.{tenantId}.inbox',
    function (
        User $user,
        int $tenantId
    ): bool {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->tenant_id ===
            (int) $tenantId;
    }
);


Broadcast::channel(
    'tenant.{tenantId}.conversation.{conversationId}',
    function (
        User $user,
        int $tenantId,
        int $conversationId
    ): bool {
        
        $conversationExists =
            Conversation::query()
                ->whereKey(
                    $conversationId
                )
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->exists();

        if (!$conversationExists) {
            return false;
        }

        
        if ($user->isSuperAdmin()) {
            return true;
        }

        
        return (int) $user->tenant_id ===
            (int) $tenantId;
    }
);