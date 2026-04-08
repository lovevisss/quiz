<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AssignRoleTest extends TestCase
{
    public function test_admin_can_assign_role_to_user(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $editorRole = Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $targetUser = User::factory()->create();

        $response = $this->actingAs($admin, 'api')->postJson('/api/users/'.$targetUser->id.'/roles', [
            'role' => 'editor',
        ]);

        $response->assertOk();
        $this->assertTrue($targetUser->fresh()->hasRole('editor'));
    }

    public function test_non_admin_user_cannot_assign_role(): void
    {
        Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);

        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/users/'.$targetUser->id.'/roles', [
            'role' => 'editor',
        ]);

        $response->assertForbidden();
    }

    public function test_assign_role_is_idempotent(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $targetUser = User::factory()->create();

        $this->actingAs($admin, 'api')->postJson('/api/users/'.$targetUser->id.'/roles', [
            'role' => 'editor',
        ])->assertOk();

        $this->actingAs($admin, 'api')->postJson('/api/users/'.$targetUser->id.'/roles', [
            'role' => 'editor',
        ])->assertOk();

        $this->assertDatabaseCount('role_user', 2);
    }
}
