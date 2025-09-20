<!DOCTYPE html>
<html>
<head>
    <title>Подтверждение email</title>
</head>
<body>
    <h1>Привет, {{ $user->name }}!</h1>
    <p>Пожалуйста, подтвердите ваш email адрес, введя код ниже:</p>

    <span>
        {{$code}}
    </span>

    <p>Если вы не регистрировались на нашем сайте, просто проигнорируйте это письмо.</p>

    <p>Код действителен в течение {{ config('auth.verification.expire', 60) }} минут.</p>
</body>
</html>
