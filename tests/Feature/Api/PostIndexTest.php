<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_posts_with_user_data(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->for($user)->create();

        $response = $this->getJson('/api/posts');

        $response
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'body',
                    'image_url',
                    'created_at',
                    'user' => ['id', 'name'],
                ],
            ]);
    }
}

