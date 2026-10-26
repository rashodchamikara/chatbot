<?php
return (static function () {
    // Presentation only. Controllers and policies remain the authority for access.
    $workspaceUser = auth()->user();
    $workspaceSuper = $workspaceUser && (method_exists($workspaceUser, 'isSuperAdmin')
        ? $workspaceUser->isSuperAdmin() : $workspaceUser->role === 'super_admin');
    $workspaceManager = $workspaceSuper || ($workspaceUser->role ?? '') === 'tenant_admin';
    $workspaceRole = $workspaceSuper ? 'System administrator' : ($workspaceManager ? 'Tenant administrator' : 'Agent workspace');
    $workspaceCatalog = require resource_path('views/workspace/channel-catalog.php');
    return compact('workspaceUser', 'workspaceSuper', 'workspaceManager', 'workspaceRole', 'workspaceCatalog');
})();
