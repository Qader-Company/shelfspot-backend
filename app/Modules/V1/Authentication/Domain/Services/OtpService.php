<?php

namespace App\Modules\V1\Authentication\Domain\Services;

use App\Modules\V1\Authentication\Application\Mail\OtpMail;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function generate(string $identifier): string
    {
        $otp = (new Otp)->generate(
            $identifier,
            'numeric',
            6,
            (int) config('sanctum.OTP_TTL'),
        );

        return $otp->token;
    }

    public function generateAndSend(string $email, OtpPurposeEnum $purpose, ?string $name = null): void
    {
        $code = $this->generate($email);
        $expiresInMinutes = (int) config('sanctum.OTP_TTL');

        Mail::to($email)
            ->locale(app()->getLocale())
            ->send(new OtpMail($code, $purpose, $name, $expiresInMinutes));
    }

    public function validate($identifier, string $token): bool
    {
        $otpCheck = (new Otp)->validate($identifier, $token);
        if (! $otpCheck->status) {
            return false;
        }
        DB::table('otps')->where('identifier', $identifier)->delete();

        return true;
    }
}
