<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    //'language' => 'ar-AR',
    //'language' => 'fa-IR',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'homeUrl' => '',
    'components' => [
        'request' => [
            'baseUrl' => '',
        ],
        'as globalSanitizer' => [
            'class' => 'app\middlewares\InputSanitizerMiddleware',
            'except' => ['some-controller/some-action'], // در صورت نیاز استثناها
        ],
        'urlManager' => [
            // 'scriptUrl'=>'/index.php',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'pattern' => 'user-register/<id:\d+>',
                    'route' => 'user-register/index',
                ],
                [
                    'pattern' => 'merchants/<id:>/<tag>',
                    'route' => 'merchants/index',
                    'defaults' => ['page' => 1, 'tag' => ''],
                ],
                [
                    'pattern' => 'showproduct/<id:\d+>/<tag>',
                    'route' => 'showproduct/index',
                    'defaults' => ['page' => 1, 'tag' => ''],
                ]
            ],

        ],
        'user' => [
            'identityClass' => 'common\models\Admin',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
            'loginUrl' => ['site'],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'userspart',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logVars' => ['_GET', '_POST'],
                    'categories' => ['yii\mongodb\*'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'transport' => [
                'plugins' => [
                    [
                        'class' => 'Swift_Plugins_ThrottlerPlugin',
                        'constructArgs' => [20],
                    ],
                    'class' => 'Swift_SmtpTransport',
                    'host' => 'smtp.gmail.com',  // e.g. smtp.mandrillapp.com or smtp.gmail.com
                    'username' => '',
                    'password' => '',
                    'port' => '587', // Port 25 is a very common port too
                    'encryption' => 'tls', // It is often used, check your provider or mail server specs
                ],
            ],
            'params' => $params,
        ],
    ]
];
