<?php

namespace App\Modules\V1\Authentication\Domain\Services;

use App\Modules\V1\Authentication\Application\Mail\OtpMail;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function generate(string $email, OtpPurposeEnum $purpose, PortalTypeEnum $portal): string
    {
        $otp = (new Otp)->generate(
            $this->identifier($email, $purpose, $portal),
            'numeric',
            6,
            (int) config('sanctum.OTP_TTL'),
        );

        return $otp->token;
    }

    public function generateAndSend(
        string $email,
        OtpPurposeEnum $purpose,
        PortalTypeEnum $portal,
        ?string $name = null
    ): void
    {
        $code = $this->generate($email, $purpose, $portal);
        $expiresInMinutes = (int) config('sanctum.OTP_TTL');

        Mail::to($email)
            ->locale(app()->getLocale())
            ->send(new OtpMail($code, $purpose, $name, $expiresInMinutes));
    }

    public function validate(string $email, string $token, OtpPurposeEnum $purpose, PortalTypeEnum $portal): bool
    {
        $identifier = $this->identifier($email, $purpose, $portal);
        $otpCheck = (new Otp)->validate($identifier, $token);
        if (! $otpCheck->status) {
            return false;
        }
        DB::table('otps')->where('identifier', $identifier)->delete();

        return true;
    }

    private function identifier(string $email, OtpPurposeEnum $purpose, PortalTypeEnum $portal): string
    {
        return implode('|', [
            strtolower($email),
            $portal->value,
            $purpose->value,
        ]);
    }
}
