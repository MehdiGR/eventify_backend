<?php

namespace Database\Seeders\Permissions;

use App\Enums\ROLE as ROLE_ENUM;
use App\Models\Role;
use App\Services\ACLService;
use Illuminate\Database\Seeder;

class CrudPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(ACLService $aclService)
    {
        // Create scoped permissions for events
        $aclService->createScopePermissions(
            'events',
            [
                'create',
                'read',
                'update',
                'delete',
                'read_own',
                'update_own',
                'delete_own',
                'register',
            ]
        );

        // Create scoped permissions for groups
        $aclService->createScopePermissions(
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

        // Fetch roles
        $adminRole = Role::where('name', ROLE_ENUM::ADMIN)->first();

        // create participant and organizer roles
        $participantRole = Role::firstOrCreate(['name' => 'participant']);
        $organizerRole = Role::firstOrCreate(['name' => 'organizer']);

        // Create additional roles for groups
        $groupAdminRole = Role::firstOrCreate(['name' => 'group_admin']);
        $groupMemberRole = Role::firstOrCreate(['name' => 'group_member']);

        // Assign permissions to event-related roles
        if ($adminRole) {
            $aclService->assignScopePermissionsToRole(
                $adminRole,
                'events',
                ['create', 'read', 'update', 'delete']
            );

            // ✅ Assign full group management permissions to ADMIN
            $aclService->assignScopePermissionsToRole(
                $adminRole,
                'groups',
                ['create', 'read', 'update', 'delete', 'invite_member', 'remove_member']
            );
        }

        $aclService->assignScopePermissionsToRole(
            $participantRole,
            'events',
            ['read', 'register']
        );

        $aclService->assignScopePermissionsToRole(
            $organizerRole,
            'events',
            ['create', 'read', 'read_own', 'update_own', 'delete_own']
        );

        // Assign permissions to group-related roles
        $aclService->assignScopePermissionsToRole(
            $groupAdminRole,
            'groups',
            ['create', 'read', 'update', 'delete', 'invite_member', 'remove_member']
        );

        $aclService->assignScopePermissionsToRole(
            $groupMemberRole,
            'groups',
            ['read', 'read_own']
        );
    }
}
