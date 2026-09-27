<?php
namespace app\validators;

use yii\validators\Validator;

class SafeOperatorValidator extends Validator
{
    public $allowedOperators = [
        '=',
        '>',
        '<',
        '>=',
        '<=',
        'LIKE',
        'NOT LIKE',
        'IN',
        'NOT IN',
        'BETWEEN',
        'NOT BETWEEN',
    ];

    public function validateAttribute($model, $attribute)
    {
        if (!in_array(strtoupper($model->$attribute), $this->allowedOperators)) {
            $this->addError($model, $attribute, 'Operator not allowed: ' . $model->$attribute);
        }
    }
}
?>