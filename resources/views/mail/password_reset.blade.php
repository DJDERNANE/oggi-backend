<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body>
    <h2>Password Reset Request</h2>
    <p>Hello {{ $user->name }},</p>
    <p>You requested a password reset. Click the link below to reset your password:</p>
    <a href="{{ $resetUrl }}">Reset Password</a>
    <p>This link will expire in 1 hour.</p>
    <p>If you didn't request this, please ignore this email.</p>
</body>
</html>