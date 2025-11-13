<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Новый запрос на доступ</title>
</head>
<body>
    <h1>Новый запрос на доступ</h1>
    <p><strong>ФИО:</strong> {{ $fullName }}</p>
    <p><strong>Организация:</strong> {{ $organization ?? '-' }}</p>
    <p><strong>Сообщение:</strong></p>
    <p>{!! nl2br(e($messageText)) !!}</p>
</body>
</html>
