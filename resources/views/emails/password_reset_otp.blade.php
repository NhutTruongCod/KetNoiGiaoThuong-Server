<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your password reset OTP</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 16px;
        }

        .otp {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 3px;
            background: #f5f5f5;
            display: inline-block;
            padding: 8px 12px;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Hello, {{ $fullName }}</h2>
        <p>Use the OTP below to reset your password:</p>
        <p class="otp">{{ $otp }}</p>
        <p>This OTP will expire in 10 minutes.</p>
        <p>If you did not request this, you can ignore this email.</p>
        <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
</body>

</html>