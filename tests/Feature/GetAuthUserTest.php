<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_a_user_can_send_a_friend_request(): void
    {
        $this->actingAs($user = User::factory()->create());
        $friend = User::factory()->create();
        $response = $this->post('api/friend-requests', [
            'friend_id' => $friend->id,
        ])->assertStatus(200);

        $friendReq = Friend::first();

        $this->assertNotNull($friendReq);
        $this->assertEquals($friend->id, $friendReq->friend_id);
        $this->assertEquals($user->id, $friendReq->user_id);
        $response->assertJson(
            [
                'data' => [
                    'type' => 'friend_requests',
                    'friend_request_id' => $friendReq->id,
                    'attributes' => [
                        'confirmed_at' => null
                    ]
                ],
                'links' => [
                    'self' => url('/users/'.$friend->id)
                ]
            ]
        );
    }

    public function test_only_valid_users_can_be_friend_request(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->post('api/friend-requests', [
            'friend_id' => 999,
        ])->assertStatus(404);

        $this->assertNull(Friend::first());

        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Not Found',
                'detail' => 'Unable to fetch friend request.',
            ]
        ]);
    }

    public function test_friend_requests_can_be_accepted(): void
    {
        $this->actingAs($user = User::factory()->create());
        $friend = User::factory()->create();
        $this->post('api/friend-requests', [
            'friend_id' => $friend->id,
        ]);
        $response = $this->actingAs($friend)->post('api/friend-requests/'.$user->id.'/accept')
            ->assertStatus(200);
        $friendRequest  = Friend::first();

        $this->assertNotNull($friendRequest->confirmed_at);

        $this->assertInstanceOf(Carbon::class, $friendRequest->confirmed_at);
        $this->assertEquals(now()->startOfSecond(), $friendRequest->confirmed_at->startOfSecond());
        $this->assertEquals(1, $friendRequest->status);
        $response->assertJson([
            'data' => [
                'type' => 'friend_requests',
                'friend_request_id' => $friendRequest->id,
                'attributes' => [
                    'confirmed_at' => $friendRequest->confirmed_at->diffForHumans(),
                ]
            ],
            'links' => [
                'self' => url('/users/'.$friend->id)
            ]
        ]);
    }
}
