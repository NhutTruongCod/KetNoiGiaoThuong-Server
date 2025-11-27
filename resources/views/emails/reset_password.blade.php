<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password</title>
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

        .btn {
            display: inline-block;
            padding: 10px 16px;
            background: #2563eb;
            color: #fff !important;
            text-decoration: none;
            border-radius: 6px;
        }

        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Hello, {{ $fullName }}</h2>
        <p>We received a request to reset your password. Use the token below or click the button to continue:</p>

        <p>Reset token: <code>{{ $token }}</code></p>

        <p style="margin:24px 0;">
            <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
        </p>

        <p>If you did not request this, you can safely ignore this email.</p>
        <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
</body>

</html>