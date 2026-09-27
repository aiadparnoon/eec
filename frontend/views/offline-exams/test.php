<nav class="navbar navbar-expand-lg navbar-light bg-light mb-5">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <?php $form = ActiveForm::begin([
                'action'=>['participants'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>
            <input type="hidden" name="_id" value="<?= (string) $examDetail->_id ?>">
            <?php
            echo $form->field($model, 'username')->textInput(
                [
                    'placeholder' => 'نام کاربری',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'orders[applicant_id]')->textInput(
                [
                    'placeholder' => 'شماره داوطلبی',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'applicant_info[id]')->textInput(
                [
                    'placeholder' => 'کد ملی',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <!--            --><?php
            //            $status = array(
            //                '0' => 'پرداخت ناموفق',
            //                '1' => 'پرداخت موفق'
            //            );
            //            echo $form->field($model, 'status')->dropDownList(
            //                $status,
            //                [
            //                    'prompt' => 'تمامی پرداخت ها',
            //                    'class' => 'form-select none-parent',
            //                    'id' => 'status',
            //                    'data-allow-clear' => true
            //                ]
            //            )->label(false);
            //            ?>
            <?php
            $utStudent = array(
                '1' => 'هستم',
                '0' => 'نیستم'
            );
            echo $form->field($model, 'applicant_info[ut_student]')->dropDownList(
                $utStudent,
                [
                    'prompt' => 'دانشجوی دکتری دانشگاه تهران',
                    'class' => 'form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            $gender = array(
                '1' => 'آقا',
                '0' => 'خانم'
            );
            echo $form->field($model, 'applicant_info[gender]')->dropDownList(
                $gender,
                [
                    'prompt' => 'جنسیت',
                    'class' => 'form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            $assistant = array(
                '1' => 'بله',
                '0' => 'خیر'
            );
            echo $form->field($model, 'applicant_info[needs_assistant]')->dropDownList(
                $assistant,
                [
                    'prompt' => 'نیازمند منشی',
                    'class' => 'form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            $lh = array(
                '1' => 'بله',
                '0' => 'خیر'
            );
            echo $form->field($model, 'applicant_info[left_handed]')->dropDownList(
                $lh,
                [
                    'prompt' => 'چپ دست',
                    'class' => 'form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            if (isset(Yii::$app->request->queryParams['ExamParticipants']))
            {
                $username = Yii::$app->request->queryParams['ExamParticipants']['username'];
//                $status = Yii::$app->request->queryParams['ExamParticipants']['status'];
                $applicantId = Yii::$app->request->queryParams['ExamParticipants']['orders']['applicant_id'];
                $utStudent = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['ut_student'];
                $id = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['id'];
                $gender = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['gender'];
                $needs_assistant = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['needs_assistant'];
                $left_handed = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['left_handed'];
            }
            else
            {
                $username = '';
//                $status = '';
                $id = '';
                $utStudent = '';
                $applicantId = '';
                $gender = '';
                $needs_assistant = '';
                $left_handed = '';
            }
            ?>
            <?php $form = ActiveForm::begin([
                'action'=>['report'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>
            <?= $form->field($model, 'username')->hiddenInput(['value' => $username])->label(false); ?>
            <!--            --><?php //= $form->field($model, 'status')->hiddenInput(['value' => $status])->label(false); ?>
            <?= $form->field($model, 'orders[applicant_id]')->hiddenInput(['value' => $applicantId])->label(false); ?>
            <?= $form->field($model, 'applicant_info[id]')->hiddenInput(['value' => $id])->label(false); ?>
            <?= $form->field($model, 'applicant_info[ut_student]')->hiddenInput(['value' => $utStudent])->label(false); ?>
            <?= $form->field($model, 'applicant_info[gender]')->hiddenInput(['value' => $gender])->label(false); ?>
            <?= $form->field($model, 'applicant_info[needs_assistant]')->hiddenInput(['value' => $needs_assistant])->label(false); ?>
            <?= $form->field($model, 'applicant_info[left_handed]')->hiddenInput(['value' => $left_handed])->label(false); ?>
            <?= $form->field($model, '_id')->hiddenInput(['value' => (string) $examDetail->_id])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
            <?php $form = ActiveForm::begin([
                'action'=>['financial_report'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>
            <?= $form->field($model, 'username')->hiddenInput(['value' => $username])->label(false); ?>
            <!--            --><?php //= $form->field($model, 'status')->hiddenInput(['value' => $status])->label(false); ?>
            <?= $form->field($model, 'orders[applicant_id]')->hiddenInput(['value' => $applicantId])->label(false); ?>
            <?= $form->field($model, 'applicant_info[id]')->hiddenInput(['value' => $id])->label(false); ?>
            <?= $form->field($model, 'applicant_info[ut_student]')->hiddenInput(['value' => $utStudent])->label(false); ?>
            <?= $form->field($model, 'applicant_info[gender]')->hiddenInput(['value' => $gender])->label(false); ?>
            <?= $form->field($model, 'applicant_info[needs_assistant]')->hiddenInput(['value' => $needs_assistant])->label(false); ?>
            <?= $form->field($model, 'applicant_info[left_handed]')->hiddenInput(['value' => $left_handed])->label(false); ?>
            <?= $form->field($model, '_id')->hiddenInput(['value' => (string) $examDetail->_id])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><defs><mask id="solarCardBold0"><g fill="none"><path fill="#fff" d="M14 4h-4C6.229 4 4.343 4 3.172 5.172c-.844.843-1.08 2.057-1.146 4.078h19.948c-.066-2.021-.302-3.235-1.146-4.078C19.657 4 17.771 4 14 4m-4 16h4c3.771 0 5.657 0 6.828-1.172C22 17.657 22 15.771 22 12c0-.442 0-.858-.002-1.25H2.002C2 11.142 2 11.558 2 12c0 3.771 0 5.657 1.172 6.828C4.343 20 6.229 20 10 20"/><path fill="#000" fill-rule="evenodd" d="M5.25 16a.75.75 0 0 1 .75-.75h4a.75.75 0 0 1 0 1.5H6a.75.75 0 0 1-.75-.75m6.5 0a.75.75 0 0 1 .75-.75H14a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75" clip-rule="evenodd"/></g></mask></defs><path fill="currentColor" d="M0 0h24v24H0z" mask="url(#solarCardBold0)"/></svg>
            </button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>
