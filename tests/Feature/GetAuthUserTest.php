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

    public function test_friend_requests_can_be_rejected(): void
    {
        $this->actingAs($user = User::factory()->create());
        $friend = User::factory()->create();
        $this->post('api/friend-requests', [
            'friend_id' => $friend->id,
        ]);
        $response = $this->actingAs($friend)->post('api/friend-requests/'.$user->id.'/reject')
            ->assertStatus(204);
        $friendRequest  = Friend::first();
        $this->assertNull($friendRequest->confirmed_at);
        $response->assertNoContent();

    }

    public function test_can_only_accept_valid_friend_request(): void
    {
        User::factory()->create();
        $friend = User::factory()->create();
        $notRequester = User::factory()->create();

        $response = $this->actingAs($friend)->post('api/friend-requests/'.$notRequester->id.'/accept')
            ->assertStatus(404);

        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Not Found',
                'detail' => 'Unable to fetch friend request.',
            ]
        ]);
    }

    public function test_a_user_id_and_status_is_required_for_friend_request_response(): void
    {
        $response = $this->actingAs($user = User::factory()->create())->post('api/friend-requests', [
            'user_id' => null,
            'status' => null,
        ])->assertStatus(422);

        $responseString = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('user_id', $responseString['errors']['meta']);
        $this->assertArrayHasKey('status', $responseString['errors']['meta']);
    }

    public function test_a_friendship_is_retrived_when_fetching_the_profile()
    {
        $this->actingAs($user = User::factory()->create());
        $friend = User::factory()->create();
        $friendRequest = Friend::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'confirmed_at' => now()->startOfSecond(),
            'status' => 1
        ]);

        $this->get('api/users/'.$user->id)
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'friendship' => [
                            'data' => [
                                'friend_request_id' => $friendRequest->id,
                                'attributes' => [
                                    'confirmed_at' => "1 day ago",
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }


}
