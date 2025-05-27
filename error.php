<?php
define('ERROR_PAGE', true);
require_once 'includes/init.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عذراً - حدث خطأ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .error-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            padding: 2rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #6c757d;
            margin: 1rem 0;
        }
        .back-button {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 0.5rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .back-button:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">⚠️</div>
        <h2>عذراً، حدث خطأ</h2>
        <p class="error-message">
            نواجه مشكلة فنية في الوقت الحالي. يرجى المحاولة مرة أخرى لاحقاً.
            <br>
            إذا استمرت المشكلة، يرجى التواصل مع مسؤول النظام.
        </p>
        <a href="/" class="back-button">العودة للرئيسية</a>
    </div>
</body>
</html>
