<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RemoveRoleTest extends TestCase
{
    public function test_admin_can_remove_role_from_user(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $editorRole = Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $targetUser = User::factory()->create();
        $targetUser->roles()->attach($editorRole->id);

        $response = $this->actingAs($admin, 'api')->deleteJson('/api/users/'.$targetUser->id.'/roles/'.$editorRole->id);

        $response->assertOk();
        $this->assertFalse($targetUser->fresh()->hasRole('editor'));
    }

    public function test_non_admin_user_cannot_remove_role(): void
    {
        Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $editorRole = Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);

        $user = User::factory()->create();
        $targetUser = User::factory()->create();
        $targetUser->roles()->attach($editorRole->id);

        $response = $this->actingAs($user, 'api')->deleteJson('/api/users/'.$targetUser->id.'/roles/'.$editorRole->id);

        $response->assertForbidden();
    }
}
