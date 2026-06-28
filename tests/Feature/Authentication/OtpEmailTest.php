<?php

namespace Tests\Feature\Authentication;

use App\Modules\V1\Authentication\Application\Mail\OtpMail;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpEmailTest extends TestCase
{
    use RefreshDatabase;

    private function companyRegistrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Company',
            'email' => 'company@example.com',
            'phone' => '+966501234567',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'cr_number' => 'CR123456',
            'industry' => 'industry_one',
        ], $overrides);
    }

    public function test_registration_sends_otp_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/company/register', $this->companyRegistrationPayload())
            ->assertSuccessful();

        Mail::assertQueued(OtpMail::class, function (OtpMail $mail) {
            return $mail->purpose === OtpPurposeEnum::EMAIL_VERIFICATION
                && strlen($mail->code) === 6;
        });
    }

    public function test_send_otp_sends_password_reset_email_for_existing_user(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'reset@example.com',
            'type' => PortalTypeEnum::COMPANY,
        ]);

        $this->postJson('/api/v1/auth/password-reset/send-otp', ['email' => 'reset@example.com'])
            ->assertSuccessful();

        Mail::assertQueued(OtpMail::class, function (OtpMail $mail) {
            return $mail->purpose === OtpPurposeEnum::PASSWORD_RESET;
        });
    }

    public function test_send_otp_does_not_send_email_when_user_missing(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/password-reset/send-otp', ['email' => 'missing@example.com'])
            ->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_registration_otp_email_uses_arabic_locale(): void
    {
        Mail::fake();

        $this->postJson(
            '/api/v1/auth/company/register',
            $this->companyRegistrationPayload(['email' => 'ar@example.com', 'phone' => '+966501234568']),
            ['Accept-Language' => 'ar']
        )->assertSuccessful();

        $expectedSubject = __('otp.subject_verification', ['app' => config('app.name')], 'ar');

        Mail::assertQueued(OtpMail::class, function (OtpMail $mail) use ($expectedSubject) {
            return $mail->envelope()->subject === $expectedSubject;
        });
    }
}
