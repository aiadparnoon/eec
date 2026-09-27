<?php
namespace app\middlewares;

use yii\base\ActionFilter;
use yii\web\Request;
use yii\web\Response;

class InputSanitizerMiddleware extends ActionFilter
{
    public function beforeAction($action)
    {
        $request = \Yii::$app->request;

        // پاکسازی پارامترهای GET
        if ($request->isGet) {
            $this->sanitize($request->get());
        }

        // پاکسازی پارامترهای POST
        if ($request->isPost) {
            $this->sanitize($request->post());
        }

        // پاکسازی پارامترهای JSON (برای APIها)
        if ($request->isAjax || $request->isPut || $request->isPatch) {
            $this->sanitize($request->getBodyParams());
        }

        return parent::beforeAction($action);
    }

    protected function sanitize(&$data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }

        return $this->cleanInput($data);
    }

    protected function cleanInput($input)
    {
        if (is_string($input)) {
            // حذف کاراکترهای خطرناک برای NoSQL Injection
            $input = preg_replace('/[\$\[\]\{\}\"\']/', '', $input);

            // تبدیل HTML خاص به entities
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // حذف اسپیس‌های اضافه
            $input = trim($input);
        }

        return $input;
    }
}
?>