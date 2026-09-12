<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;


Broadcast::channel(
    'App.Models.User.{id}',
    function ($user, $id): bool {
        return (int) $user->id ===
            (int) $id;
    }
);


$canAccessTenant =
    static function (
        $user,
        $tenantId
    ): bool {
        /*
         * Reject suspended/inactive users.
         */
        if (
            isset($user->status) &&
            $user->status !== 'active'
        ) {
            return false;
        }

        /*
         * Prefer the model helper if available.
         */
        if (
            method_exists(
                $user,
                'isSuperAdmin'
            ) &&
            $user->isSuperAdmin()
        ) {
            return true;
        }

        /*
         * Fallback for installations where the
         * helper has not yet been added.
         */
        if (
            ($user->role ?? null) ===
            'super_admin'
        ) {
            return true;
        }

        /*
         * Normal tenant users can only access
         * their own tenant.
         */
        if ($user->tenant_id === null) {
            return false;
        }

        return (int) $user->tenant_id ===
            (int) $tenantId;
    };


Broadcast::channel(
    'tenant.{tenantId}.inbox',
    function (
        User $user,
        int $tenantId
    ): bool {
        /*
         * Super admins may inspect every tenant.
         */
        if (
            method_exists(
                $user,
                'isSuperAdmin'
            )
            &&
            $user->isSuperAdmin()
        ) {
            return true;
        }

        return (int) $user->tenant_id
            ===
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
        /*
         * First validate user tenant access.
         */
        $hasTenantAccess =
            (
                method_exists(
                    $user,
                    'isSuperAdmin'
                )
                &&
                $user->isSuperAdmin()
            )
            ||
            (
                (int) $user->tenant_id
                ===
                (int) $tenantId
            );

        if (!$hasTenantAccess) {
            return false;
        }

       
        return Conversation::query()
            ->whereKey(
                $conversationId
            )
            ->where(
                'tenant_id',
                $tenantId
            )
            ->exists();
    }
);