<?php

namespace Tests\Feature;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sanctum.OTP_TTL', 10);
        $this->otpService = app(OtpService::class);
    }

    public function test_generated_otp_can_be_validated_only_once(): void
    {
        $email = 'worker@example.com';
        $code = $this->otpService->generate(
            $email,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        );

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->otpService->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        ));
        $this->assertFalse($this->otpService->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        ));
    }

    public function test_incorrect_otp_is_rejected_without_consuming_the_real_code(): void
    {
        $email = 'worker@example.com';
        $code = $this->otpService->generate(
            $email,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        );
        $incorrectCode = $code === '000000' ? '999999' : '000000';

        $this->assertFalse($this->otpService->validate(
            $email,
            $incorrectCode,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        ));
        $this->assertTrue($this->otpService->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        ));
    }

    public function test_expired_otp_is_rejected(): void
    {
        $email = 'worker@example.com';
        $code = $this->otpService->generate(
            $email,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        );

        DB::table('otps')->update([
            'created_at' => now()->subMinutes(11),
        ]);

        $this->assertFalse($this->otpService->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        ));
    }

    public function test_otp_is_scoped_to_its_purpose_and_portal(): void
    {
        $email = 'person@example.com';
        $code = $this->otpService->generate(
            $email,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        );

        $this->assertFalse($this->otpService->validate(
            $email,
            $code,
            OtpPurposeEnum::PASSWORD_RESET,
            PortalTypeEnum::WORKER,
        ));
        $this->assertFalse($this->otpService->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::COMPANY,
        ));
        $this->assertTrue($this->otpService->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::WORKER,
        ));
    }
}
