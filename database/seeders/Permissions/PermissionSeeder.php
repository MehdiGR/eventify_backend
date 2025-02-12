<?php

namespace Database\Seeders\Permissions;

use App\Enums\ROLE as ROLE_ENUM;
use App\Models\Role;
use App\Services\ACLService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
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
        $userRole = $this->aclService->createRole(ROLE_ENUM::USER);
        $adminRole = $this->aclService->createRole(ROLE_ENUM::ADMIN);
        // i've add Participant and Organizer roles
        $participantRole = $this->aclService->createRole(ROLE_ENUM::PARTICIPANT);
        $organizerRole = $this->aclService->createRole(ROLE_ENUM::ORGANIZER);

        // Create scoped permissions
        $this->aclService->createScopePermissions('users', ['create', 'read', 'update', 'delete']);
        // Create scoped permissions for events
        $this->aclService->createScopePermissions('events', ['create', 'read', 'update', 'delete', 'register']);

        // Assign permissions to roles
        $this->aclService->assignScopePermissionsToRole($adminRole, 'users', ['create', 'read', 'update', 'delete']);
        $this->aclService->assignScopePermissionsToRole($adminRole, 'events', ['create', 'read', 'update', 'delete']);

        // assign permission to PARTICIPANT AND ORGANIZER
        $this->aclService->assignScopePermissionsToRole($participantRole, 'events', ['read', 'register']);
        $this->aclService->assignScopePermissionsToRole($organizerRole, 'events', ['create', 'read', 'update', 'delete']);
    }

    public function rollback()
    {
        $adminRole = Role::where('name', ROLE_ENUM::ADMIN)->first();
        $this->aclService->removeScopePermissionsFromRole($adminRole, 'users', ['create', 'read', 'update', 'delete']);
    }
}
