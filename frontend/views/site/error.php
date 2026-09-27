<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
$back = Yii::getAlias('@back');
if(Yii::$app->response->statusCode==403 || Yii::$app->response->statusCode==404)
    $this->title = 'عدم دسترسی';
else
    $this->title = 'صفحه مورد نظر یافت نشد';
?>
<?php
if(Yii::$app->response->statusCode==403 || Yii::$app->response->statusCode==400 || Yii::$app->response->statusCode==404)
{
    ?>

    <div class="card">
        <div class="card-body">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6>کاربر گرامی شما اجازه دسترسی به این صفحه را ندارید</h6>
            </div>

            <div class="row mt-5">
                <div class="col-3"></div>
                <div class="col-6">
                    <img src="<?php echo $back; ?>/images/401.png" class="img-fluid">
                </div>
                <div class="col-3"></div>
            </div>

        </div>
    </div>
    <?php
}
?>
