<?php

namespace Database\Seeders\Permissions;

use App\Enums\ROLE as ROLE_ENUM;
use App\Models\Role;
use App\Services\ACLService;
use Illuminate\Database\Seeder;

class EventPermissionSeeder extends Seeder
{
    private ACLService $aclService;

    public function __construct(ACLService $aclService)
    {
        $this->aclService = $aclService;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define roles
        $participantRole = $this->aclService->createRole(ROLE_ENUM::PARTICIPANT);
        $organizerRole = $this->aclService->createRole(ROLE_ENUM::ORGANIZER);
        $adminRole = $this->aclService->createRole(ROLE_ENUM::ADMIN);

        // Create scoped permissions for events
        $this->aclService->createScopePermissions(
            'events',
            [
                'create', 'read', 'update', 'delete',
                'read_own', 'update_own', 'delete_own', 'register',
            ]
        );

        // Assign permissions to roles
        $this->aclService->assignScopePermissionsToRole(
            $participantRole, 'events',
            [
                'read',
                'register',
            ]
        );
        $this->aclService->assignScopePermissionsToRole(
            $organizerRole,
            'events', [
                'create',
                'read',
                'read_own',
                'update_own',
                'delete_own',
            ]
        );
        $this->aclService->assignScopePermissionsToRole(
            $adminRole, 'events',
            [
                'create', 'read', 'update', 'delete',
            ]
        );
    }

    public function rollback()
    {
        $organizerRole = Role::where('name', ROLE_ENUM::ORGANIZER)->first();
        $this->aclService->removeScopePermissionsFromRole($organizerRole, 'users', ['create', 'read', 'update', 'delete']);
    }
}
