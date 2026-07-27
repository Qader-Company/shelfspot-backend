<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your worker account</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 480px; margin: 0 auto; padding: 24px;">
    <p>Hello {{ $name }},</p>
    <p>Your worker account has been created. Use the following details to sign in:</p>
    <p style="margin: 24px 0; padding: 16px; background: #f6f8fa; border-radius: 6px;">
        <strong>Email:</strong> {{ $email }}<br>
        <strong>Password:</strong> {{ $password }}
    </p>
    <p>
        <a href="{{ $applicationUrl }}" style="display: inline-block; padding: 12px 18px; color: #fff; background: #2563eb; border-radius: 6px; text-decoration: none;">Open the application</a>
    </p>
    <p style="font-size: 13px; color: #666; margin-top: 32px;">For your security, change your password after you sign in.</p>
</body>
</html>
