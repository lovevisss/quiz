<?php
namespace Tests\Feature;

use App\Models\Friend;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetrievePostsTest extends TestCase
{
    use RefreshDatabase;
    public function test_retrieving_posts()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();

        $posts = Post::factory()->count(2)->create([
            'user_id' => $anotherUser->id,
                ]
        );
        Friend::create(
            [
                'user_id' => $user->id,
                'friend_id' => $anotherUser->id,
                'confirmed_at' => now(),
                'status' => 1
            ]
        );
        $response = $this->get('/apis/posts');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'post_id' => $posts->last()->id,
                        'attributes' => [
                            'body' => $posts->last()->body,
                            'image' => $posts->last()->image_url,
                            'posted_at' => $posts->last()->created_at->diffForHumans(),
                        ]
                    ]
                ],
                [
                    'data' => [
                        'type' => 'posts',
                        'post_id' => $posts->first()->id,
                        'attributes' => [
                            'body' => $posts->first()->body,
                            'image' => $posts->first()->image_url,
                            'posted_at' => $posts->first()->created_at->diffForHumans(),
                        ]
                    ]
                ]
            ]
        ]);
    }



    public function test_a_user_can_view_user_profiles()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $posts = Post::factory()->count(1)->create();

        $response = $this->get('/api/users/'.$user->id);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'type' => 'users',
                'user_id' => $user->id,
                'attributes' => [
                    'name' => $user->name,
                ]
            ],
            'links' => [
                'self' => url('/users/'.$user->id)
            ]
        ]);
    }

    public function test_a_user_can_fetch_a_post(){
        $this->actingAs($user = User::factory()->create(), 'api');
        $post = Post::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->get('/api/users/'.$user->id.'/posts');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'post_id' => $post->id,
                        'attributes' => [
                            'body' => $post->body,
                            'image' => $post->image_url
                        ]
                    ]
                ]
            ]
        ]);
    }
}
