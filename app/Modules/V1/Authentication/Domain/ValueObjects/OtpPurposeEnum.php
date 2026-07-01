<?php

namespace App\Modules\V1\Authentication\Domain\ValueObjects;

enum OtpPurposeEnum: string
{
    case EMAIL_VERIFICATION = 'email-verification';
    case PASSWORD_RESET = 'password-reset';

    public static function getTypes() : array
    {
        return array_map(
            fn(self $item) => [
                'value' => $item->value,
            ], self::cases()
        );
    }

    public function subjectKey(): string
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => 'otp.subject_verification',
            self::PASSWORD_RESET => 'otp.subject_password_reset',
        };
    }

    public function greetingKey(): string
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => 'otp.greeting_verification',
            self::PASSWORD_RESET => 'otp.greeting_password_reset',
        };
    }

    public static function values() : array
    {
        return array_map(
            fn ($case) => $case->value,
            self::cases()
        );
    }
}
