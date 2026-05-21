<?php

namespace App\Modules\V1\Authentication\Domain\Services;

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;

class OtpService
{
    public function generate($identifier): string
    {
        $otp = (new Otp)->generate(
            $identifier,
            'numeric',
            6,
            $validity ?? config('sanctum.OTP_TTL'),
        );
        return $otp->token;
    }

     public function validate($identifier, string $token): bool
     {
         $otpCheck = (new Otp)->validate($identifier, $token);
         if (!$otpCheck->status) {
             return false;
         }
         DB::table('otps')->where('identifier', $identifier)->delete();
         return true;
     }

//    public function validate($identifier, string $token): bool
//    {
//        if ($token != 123456) {
//            return false;
//        }
//        return true;
//    }
}
