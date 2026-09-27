<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\Application;

class InputSanitizerMiddleware extends Component
{
    public function init()
    {
        Yii::$app->on(Application::EVENT_BEFORE_REQUEST, function ($event) {
            $this->sanitizeInput();
        });
    }

    protected function sanitizeInput()
    {
        $request = Yii::$app->request;

        // پاکسازی ورودی های GET
        if ($request->get()) {
            $_GET = $this->sanitizeData($_GET);
        }

        // پاکسازی ورودی های POST
        if ($request->post()) {
            $_POST = $this->sanitizeData($_POST);
        }

        // پاکسازی ورودی های COOKIE
        if ($request->cookies) {
            foreach ($request->cookies as $name => $cookie) {
                $cookieValue = $cookie->value;
                if (is_string($cookieValue)) {
                    $cookie->value = $this->sanitizeString($cookieValue);
                }
            }
        }
    }

    protected function sanitizeData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeData($value);
            }
        } elseif (is_string($data)) {
            $data = $this->sanitizeString($data);
        }

        return $data;
    }

    protected function sanitizeString($string)
    {
        // حذف کاراکترهای خاص و اسکریپت ها
        $string = strip_tags($string);
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');

        // جلوگیری از NoSQL Injection (مثال ساده)
        $string = str_replace(['$', '{', '}'], '', $string);

        return $string;
    }
}
