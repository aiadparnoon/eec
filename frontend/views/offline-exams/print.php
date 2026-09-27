<?php

$script = <<< JS
    $('.print-button').click(function() {
        $('.print-button').css("opacity", '0')
        window.print();
    });

    window.onafterprint = function() {
        $('.print-button').css("opacity", '1')
    };
JS;

$this->registerJs($script);

?>

<style id="print-style">
    @page {
        size: 8.3in 11.7in;
        margin: 0;
    }

    .print-container {
        width: 95%;
        max-width: 1000px;
        margin-top: 1rem;
        background-color: white;
        border-radius: 20px;
        padding: 0;
        margin: 0 auto !important;
    }

    .content {
        margin-top: 1rem;
        /* margin-left: 1rem;
        margin-right: 1rem; */
        padding: 1rem;
        border: 2px solid black;
    }


    .logo {
        width: 50px;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .title {
        margin-top: 0.5rem;
        display: block;
        font-weight: bold;
        font-size: 1.125rem;
        text-align: center;
    }

    .date {
        margin-top: 0.5rem;
        display: block;
        font-weight: bold;
        text-align: center;
    }

    .details {
        margin-top: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-evenly;
        gap: 2rem;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(4, auto);
        gap: 2.5rem 1rem;
    }

    .photo {
        width: 10rem;
        height: 14rem;
        border: 1px solid black;
        overflow: hidden;
    }

    .photo img {
        display: block;
        width: 100%;
        height: 100%;
    }

    .tips {
        margin-top: 1.5rem;
        padding: 1.5rem;
        line-height: 2rem;
        border-top: 1px solid black;
    }

    .print-button {
        margin-top: 1rem;
        width: 100%;
    }
</style>

<?php
if($orderDetail->applicant_info != null)
{
    ?>
    <div class="print-container">
        <div class="content" dir="rtl">
            <img src="https://eec1.ut.ac.ir/assets/images/logo.png" alt="ut.ac" class="logo" />
            <span class="title">
            مجوز ورود به جلسه <?= $examDetail->title['fa'] ?>
        </span>
            <span class="date"><?= $examDetail->start_date ?></span>
            <div class="details">
                <div class="grid">
                    <span>نام:</span>
                    <span><?= $orderDetail->applicant_info['first_name'] ?></span>
                    <span>نام خانوادگی:</span>
                    <span><?= $orderDetail->applicant_info['last_name'] ?></span>
                    <span>نام پدر:</span>
                    <span><?= $orderDetail->applicant_info['father_name'] ?></span>
                    <span>کدملی:</span>
                    <span><?= $orderDetail->applicant_info['id'] ?></span>
                    <span>شماره داوطلبی:</span>
                    <span><?= $orderDetail->orders[0]['applicant_id'] ?></span>
                    <span>نوع زبان خارجی:</span>
                    <span>انگلیسی</span>
                    <span class="col-span-2">محل برگزاری آزمون:</span>
                    <span class="col-span-2">دانشکده زبان ها و ادبیات خارجی</span>
                </div>

                <div class="photo">
                    <img src="https://eec1.ut.ac.ir/users_profile/<?= $orderDetail->applicant_info['profile_image'] ?>" alt="[userState.applicant_info?.first_name]" />
                </div>
            </div>

            <div class="tips">
                <?= $examDetail->tips ?>
            </div>
        </div>
        <button class="print-button btn btn-info" type="button">
            چاپ
        </button>
    </div>
<?php
}
?>