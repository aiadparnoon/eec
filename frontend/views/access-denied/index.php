<?php
$this->title = 'عدم دسترسی';
$front = Yii::getAlias('@front');
?>
<div class="card">
    <div class="card-body">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6>کاربر گرامی شما اجازه دسترسی به این صفحه را ندارید</h6>
        </div>

        <div class="row mt-5">
            <div class="col-3"></div>
            <div class="col-6">
                <img src="<?php echo $front; ?>/assets/images/401.png" class="img-fluid">
            </div>
            <div class="col-3"></div>
        </div>

    </div>
</div>