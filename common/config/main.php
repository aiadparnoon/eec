<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@images'=>'/frontend/web/images',
        '@img'=>'/frontend/web/img',
        '@main_images'=>'/frontend/web/images',
        '@userimages'=>'/userpanel/web/images',
        '@adminimage'=>'/backend/web/images',
        '@dashboard_images'=>'/frontend/web/dashboard/img',
        '@back'=>'/backend/web',
        '@front'=>'',
        '@member_profiles'=>'/backend/web/member_profiles',
        '@examImages'=>'/backend/web/exam_preview_images',
        '@bookImages'=>'/backend/web/book_preview_images',
        '@baseUrl'=>'http://api-eec.ut.ac.ir',
        '@adminUsername'=>'09302521408',
        '@maxFileSize'=>'102400',
        '@protectFolder' => '/frontend/protected_folders',
        '@ff' => '/frontend',
        '@uploadSize' => '100000',
        '@adobe_user' => 'eecadmin',
        // '@adobe_password' در common/config/main-local.php تعریف می‌شود (خارج از git).
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'modules' => [
        // امنیتی (۲۰۲۶-۰۸-۲۹): ماژول 'gii1' از اینجا حذف شد.
        // Gii یک تولیدکننده‌ی کد است و می‌تواند فایل PHP روی سرور بنویسد. چون در این فایل
        // (common) ثبت شده بود، روی *همه‌ی* اپلیکیشن‌ها فعال می‌شد و چون Gii احراز هویت
        // ندارد (فقط محدودیت IP)، عملاً یک مسیر اجرای کد روی سرور بود.
        // اگر برای توسعه‌ی محلی لازمش داشتید، فقط در frontend/config/main-local.php
        // همان دستگاه اضافه‌اش کنید - نه در این فایل که روی سرور هم می‌رود.
        'gridview' => [
            'class' => 'kartik\grid\Module',
            // other module settings
        ]

    ],

    'language'=>'fa-IR',
    'homeUrl' => '/parseh',
    'components' => [
        'assetManager' => [
            'bundles' => [
                'dosamigos\google\maps\MapAsset' => [
                    'options' => [
                        'key' => 'AIzaSyCfB6FF1yMI2fqYdnnYOeiFboE37V5e3fM',
                        'language' => 'id',
                        'version' => '3.1.18'
                    ]
                ]
            ]
        ],
        'jdate' => [
            'class' => 'jDate\DateTime'
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'request' => [
            'baseUrl' => '/parseh', // localhost/project

        ],
        'as beforeRequest' => [ // اضافه کردن middleware به صورت global
            'class' => 'app\components\middleware\InputSanitizerMiddleware',
        ],
        'urlManager' => [

            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
//                '<controller:\w+>/<id:\d+>' => '<controller>/view',
//                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
//                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',

                'test'=>'testrest/test',
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'saverequestdetail',
                    'except' => ['delete', 'create', 'update'],
                ],


            ],
        ],
//        'soapClient' => [
//            'class' => \radomirradojevic\soap\SoapClientWrapper::className(),
//            'url' => 'https://bos.bpm.bankmellat.ir/bhws/Services/bpm/VirtualAccountService.asmx?wsdl',
//            // SoapClient options
//            'options' => [
//                'cache_wsdl' => WSDL_CACHE_NONE,
//                'debug' => true,
//            ],
//            // SopaClient headers, object or closure
//            'headers' => function() {
//                $headers = new stdClass();
//                $headers->authDetails = new stdClass(); // This is node in SOAP Header where the login and password.
//                $headers->authDetails->wss_ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
//                $headers->authDetails->login = 'ParseVcc';
//                $headers->authDetails->password = '54050778';
//                return $headers;
//            }
//        ],
    ],
];
