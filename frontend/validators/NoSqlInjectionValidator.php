<?php
namespace app\validators;

use yii\validators\Validator;

class NoSqlInjectionValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        if (preg_match('/[$]/', $model->$attribute)) {
            $this->addError($model, $attribute, 'Invalid characters detected.');
        }
    }
}
?>