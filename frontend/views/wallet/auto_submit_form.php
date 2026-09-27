<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $autoSubmit boolean */
/* @var $formAction string */
/* @var $formData array */

$this->title = 'Auto Submit Form';

if ($autoSubmit): ?>
    <?php
    $form = ActiveForm::begin(
        [
            'action' => 'https://bpm.shaparak.ir/pgwchannel2/startpay.mellat',
//            'action' => ['test'],
            "method" => "post",
            "id" => "autoSubmitForm",
        ]
    ); ?>
    <?php foreach ($formData as $key => $value): ?>
        <input type="hidden" id="RefId" name="<?= Html::encode($key) ?>" value="<?= Html::encode($value) ?>">
    <?php endforeach; ?>
    <input type="hidden" name="_csrf" value="<?=Yii::$app->request->getCsrfToken()?>" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('autoSubmitForm').submit();
        });
    </script>
    <?php ActiveForm::end(); ?>
<?php endif; ?>
