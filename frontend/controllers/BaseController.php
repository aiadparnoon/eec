<?php
namespace frontend\controllers;

use yii\web\Controller;
use Yii;
use app\models\UserExams;
use app\models\Exams;

class BaseController extends Controller
{
    // در BaseController
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // چک کردن نظرسنجی
        $excludedRoutes = ['poll/*', 'site/*', 'auth/*'];
        $currentRoute = Yii::$app->controller->module->id . '/' . Yii::$app->controller->id . '/' . $action->id;

        foreach ($excludedRoutes as $route) {
            if (fnmatch($route, $currentRoute)) {
                return true;
            }
        }

        if (!Yii::$app->user->isGuest)
        {
            if(Yii::$app->user->identity->role == 'user')
                return true;
            $role = Yii::$app->user->identity->role;
            $type = -1;
            if($role == 'teacher')
                $type = '2';
            else if($role == 'emp')
                $type = '3';
            else if($role == 'broker')
                $type = '4';
            $allExams = Exams::find()->where(['management' => true])->andWhere(['type' => $type])->all();
            if($allExams == null)
                return true;
            $hasFilledPoll = UserExams::find()
                ->where(['user_id' => (string) Yii::$app->user->identity->_id])
                ->exists();

            if (!$hasFilledPoll) {
                Yii::$app->session->setFlash('warning', 'لطفاً ابتدا نظرسنجی را تکمیل کنید');
                $this->redirect(['/poll'])->send();
                return false;
            }
        }

        return true;
    }
}