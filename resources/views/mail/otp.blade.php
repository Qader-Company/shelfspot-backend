<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 480px; margin: 0 auto; padding: 24px;">
    <p>{{ $greeting }}</p>
    <p style="margin: 24px 0 8px; font-size: 14px; color: #666;">{{ __('otp.code_label') }}</p>
    <p style="font-size: 32px; font-weight: bold; letter-spacing: 8px; margin: 0 0 24px;">{{ $code }}</p>
    <p style="font-size: 14px; color: #666;">{{ __('otp.expires_in', ['minutes' => $expiresInMinutes]) }}</p>
    <p style="font-size: 13px; color: #999; margin-top: 32px;">{{ __('otp.do_not_share') }}</p>
</body>
</html>
