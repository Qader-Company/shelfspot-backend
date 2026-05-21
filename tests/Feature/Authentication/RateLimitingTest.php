<?php

namespace Tests\Feature\Authentication;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_login_is_throttled_after_max_attempts(): void
    {
        $payload = [
            'email' => 'throttle-login@example.com',
            'password' => 'WrongPass1',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/company/login', $payload)
                ->assertStatus(401);
        }

        $response = $this->postJson('/api/v1/auth/company/login', $payload);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
            ])
            ->assertHeader('Retry-After');
    }

    public function test_send_otp_is_throttled_per_email(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'otp-limit@example.com',
            'type' => PortalTypeEnum::COMPANY,
        ]);

        $url = '/api/v1/auth/password-reset/send-otp';
        $payload = ['email' => 'otp-limit@example.com'];

        for ($i = 0; $i < 3; $i++) {
            $this->postJson($url, $payload)->assertSuccessful();
        }

        $response = $this->postJson($url, $payload);

        $response->assertStatus(429)
            ->assertJson(['success' => false])
            ->assertHeader('Retry-After');
    }
}
