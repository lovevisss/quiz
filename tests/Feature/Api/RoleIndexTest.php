<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleIndexTest extends TestCase
{
    public function test_authenticated_user_can_fetch_roles(): void
    {
        Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        Role::query()->create(['name' => 'Editor', 'slug' => 'editor']);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/roles');

        $response->assertOk();
        $response->assertJsonPath('data.0.data.attributes.slug', 'admin');
        $response->assertJsonPath('data.1.data.attributes.slug', 'editor');
    }
}
