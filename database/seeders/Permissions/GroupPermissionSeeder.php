<?php

// Add new roles for groups
$groupAdminRole = $this->aclService->createRole('group_admin');
$groupMemberRole = $this->aclService->createRole('group_member');

// Create scoped permissions for groups
$this->aclService->createScopePermissions(
    'groups',
    [
        'create',
        'read',
        'update',
        'delete',
        'read_own',
        'update_own',
        'delete_own',
        'invite_member',
        'remove_member',
    ]
);

// Assign permissions to roles
$this->aclService->assignScopePermissionsToRole(
    $groupAdminRole,
    'groups',
    [
        'create',
        'read',
        'update',
        'delete',
        'invite_member',
        'remove_member',
    ]
);

$this->aclService->assignScopePermissionsToRole(
    $groupMemberRole,
    'groups',
    [
        'read',
        'read_own',
    ]
);
