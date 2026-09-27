<?php


namespace app\components\middleware;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\Request;

class InputSanitizerMiddleware extends Behavior
{
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    public function beforeAction($event)
    {
        $request = Yii::$app->request;

        // پاکسازی ورودی‌های GET
        $get = $this->sanitizeData($request->get());
        $_GET = $get; // جایگزینی GET با داده‌های پاکسازی شده

        // پاکسازی ورودی‌های POST
        $post = $this->sanitizeData($request->post());
        $_POST = $post; // جایگزینی POST با داده‌های پاکسازی شده
    }

    /**
     * تابع اصلی پاکسازی داده‌ها
     * @param array $data
     * @return array
     */
    private function sanitizeData(array $data): array
    {
        $sanitizedData = [];
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeString($key);
            if (is_array($value)) {
                $sanitizedValue = $this->sanitizeData($value);
            } else {
                $sanitizedValue = $this->sanitizeInput($value);
            }
            $sanitizedData[$sanitizedKey] = $sanitizedValue;
        }
        return $sanitizedData;
    }

    /**
     * پاکسازی یک ورودی خاص (استفاده از فیلترهای PHP)
     * @param $input
     * @return string
     */
    private function sanitizeInput($input): string
    {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        $input = filter_var($input, FILTER_SANITIZE_STRING); // حذف تگ‌های HTML
        return $input;
    }

    /**
     * پاکسازی رشته (استفاده از عبارات با قاعده)
     * @param $string
     * @return string
     */
    private function sanitizeString($string): string
    {
        $string = preg_replace('/[^a-zA-Z0-9_\-]/', '', $string); // حذف کاراکترهای غیرمجاز
        return $string;
    }
}

?>