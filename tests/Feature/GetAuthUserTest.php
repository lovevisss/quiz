<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAuthUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_user_can_be_fetched(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/user');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', $user->name);
        $response->assertJsonPath('data.attributes.user_id', $user->id);
        $response->assertJsonPath('links.self', url('/users/'.$user->id));
    }
}
