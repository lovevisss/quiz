<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        dump($user);

        $this->assertNotNull($user->id);
        $this->assertInstanceOf(User::class, $user);
    }
}