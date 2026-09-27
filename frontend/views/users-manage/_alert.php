<?php
/** @var string $type @var string $message */
use yii\helpers\Html;
?>
<div class="alert alert-<?= Html::encode($type) ?> mb-0" role="alert"><?= Html::encode($message) ?></div>
