<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\CasClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CasAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_redirect_route_sends_user_to_cas_login_url(): void
    {
        $casClient = Mockery::mock(CasClient::class);
        $casClient
            ->shouldReceive('buildLoginUrl')
            ->once()
            ->andReturn('https://cas.paas.zufedfc.edu.cn/cas/login?service=encoded');

        $this->app->instance(CasClient::class, $casClient);

        $response = $this->get('/auth/cas/redirect');

        $response->assertRedirect('https://cas.paas.zufedfc.edu.cn/cas/login?service=encoded');
    }

    public function test_callback_creates_and_logs_in_user_when_ticket_is_valid(): void
    {
        config()->set('cas.auto_register', true);

        $casClient = Mockery::mock(CasClient::class);
        $casClient
            ->shouldReceive('validateTicket')
            ->once()
            ->andReturn([
                'username' => '20240001',
                'attributes' => [
                    'gh' => '20240001',
                    'name' => 'Test Student',
                    'email' => '20240001@zufedfc.edu.cn',
                ],
            ]);

        $this->app->instance(CasClient::class, $casClient);

        $response = $this->get('/auth/cas/callback?ticket=ST-1-test');

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Test Student',
            'email' => '20240001@zufedfc.edu.cn',
            'student_id' => '20240001',
        ]);
    }
}

