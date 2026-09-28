<?php
return [
    // Secrets: fill in on each server, never commit real values.
    'aliases' => [
        '@adobe_password' => '',
        '@payment_api_key' => '',
        // کلید رمزنگاری تنظیمات محرمانه‌ی دیتابیس (رمز سرورها)؛ خالی = cookieValidationKey
        '@settings_key' => '',
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=yii2advanced',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
    ],
];
