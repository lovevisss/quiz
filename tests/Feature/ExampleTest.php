<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response()
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
    }

    public function test_a_user_can_like_a_post()
    {
        $this->actingAs($user = \App\Models\User::factory()->create());
        $post = Post::factory()->create([
            'id' => 123
        ]);
        $response = $this->post('/api/posts/' . $post->id . '/like');
        $response->assertStatus(200);
        $this->assertCount(1, $user->likedPosts);
        $response->assertJson([
            'data' => [
                [
                    'data' => [
                        'type' => 'likes',
                        'liked_id' => 1,
                        'attributes' => []
                    ],
                    'links' => [
                        'self' => url('/posts/' . $post->id),
                    ]
                ]

            ],
            'links' => [
                'self' => url('/posts/' . $post->id),
            ],
        ]);
    }

    public function test_posts_are_returned_with_likes()
    {
        $this->actingAs($user = \App\Models\User::factory()->create());
        $post = Post::factory()->create([
            'id' => 123,
            'user_id' => $user->id,
        ]);
        $response = $this->post('/api/posts/' . $post->id . '/like');
        $response->assertStatus(200);

        $response = $this->get('/api/posts')->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'data' => [
                            'type' => 'posts',
                            'attributes' => [
                                'likes' => [
                                    'data' => [
                                        'type' => 'likes',
                                        'id' => 1,
                                        'attributes' => [],
                                    ],
                                    'like_count' => 1,
                                    'user_likes_post' => true,
                                ],
                            ]
                        ]
                    ]
                ]
            ]);
    }
}
