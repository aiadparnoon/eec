<?php
$this->title = 'ایجاد درس دوره';
use yii\widgets\ActiveForm;
use frontend\assets\SingleAsset;
use app\models\Courses;
SingleAsset::register($this);
$model = new Courses();
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">مدیریت دوره /</span> ایجاد درس دوره
    </h4>
    <div class="row">
        <!-- Basic Layout -->
        <div class="col-xxl">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">فرم ایجاد درس دروه</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action'=>['new'],
                            'options' => [
                                'class' => 'form form-horizontal',
                                'enctype'=>'multipart/form-data'
                            ],
                        ]
                    ); ?>
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label text-sm-end" for="collapsible-fullname">قیمت دوره (تومان) *</label>
                                        <div class="col-sm-9">
                                            <?= $form->field($model, 'price')->textInput(
                                                [
                                                    'class' => 'form-control',
                                                    'type' => 'number',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label text-sm-end" for="collapsible-phone">شماره تلفن</label>
                                        <div class="col-sm-9">
                                            <input type="text" id="collapsible-phone" class="form-control phone-mask text-start" placeholder="658 799 8941" dir="ltr" aria-label="658 799 8941">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label text-sm-end" for="collapsible-address">آدرس</label>
                                        <div class="col-sm-9">
                                            <textarea name="collapsible-address" class="form-control" id="collapsible-address" rows="4" placeholder="بلوار نیایش"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="row">
                                            <label class="col-sm-3 col-form-label text-sm-end" for="collapsible-pincode">پین‌کد</label>
                                            <div class="col-sm-9">
                                                <input type="text" id="collapsible-pincode" class="form-control text-start" placeholder="658468" dir="ltr">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="row">
                                            <label class="col-sm-3 col-form-label text-sm-end" for="collapsible-landmark">نشان اختصاصی</label>
                                            <div class="col-sm-9">
                                                <input type="text" id="collapsible-landmark" class="form-control" placeholder="ساختمان بنفشه">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label text-sm-end" for="collapsible-city">شهر</label>
                                        <div class="col-sm-9">
                                            <input type="text" id="collapsible-city" class="form-control" placeholder="تبریز">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label text-sm-end" for="collapsible-state">استان</label>
                                        <div class="col-sm-9">
                                            <div class="position-relative"><select id="collapsible-state" class="select2 form-select select2-hidden-accessible" data-allow-clear="true" data-select2-id="collapsible-state" tabindex="-1" aria-hidden="true">
                                                    <option value="" data-select2-id="16">انتخاب</option>
                                                    <option value="AL">آذربایجان شرقی</option>
                                                    <option value="AK">آذربایجان غربی</option>
                                                    <option value="AZ">اردبیل</option>
                                                    <option value="AR">اصفهان </option>
                                                    <option value="CA">البرز </option>
                                                    <option value="CO">ایلام </option>
                                                    <option value="CT">بوشهر </option>
                                                    <option value="DE">تهران </option>
                                                    <option value="DC">چهارمحال و بختیاری</option>
                                                    <option value="FL">خراسان جنوبی</option>
                                                    <option value="GA">خراسان رضوی</option>
                                                    <option value="HI">خراسان شمالی</option>
                                                    <option value="ID">خوزستان </option>
                                                    <option value="IL">زنجان </option>
                                                    <option value="IN">سمنان </option>
                                                    <option value="IA">سیستان و بلوچستان</option>
                                                    <option value="KS">فارس </option>
                                                    <option value="KY">قزوین </option>
                                                    <option value="LA">قم </option>
                                                    <option value="ME">کردستان </option>
                                                    <option value="MD">کرمان </option>
                                                    <option value="MA">کرمانشاه </option>
                                                    <option value="MI">کهگیلویه و بویراحمد</option>
                                                    <option value="MN">گلستان </option>
                                                    <option value="MS">گیلان </option>
                                                    <option value="MO">لرستان</option>
                                                    <option value="MT">مازندران </option>
                                                    <option value="NE">مرکزی </option>
                                                    <option value="NV">هرمزگان </option>
                                                    <option value="NH">همدان </option>
                                                    <option value="NJ">یزد</option>
                                                    <option value="NM">کرج</option>
                                                    <option value="NY">تبریز</option>
                                                    <option value="NC">لورم ایپسوم متن</option>
                                                    <option value="ND">قم</option>
                                                    <option value="OH">لورم</option>
                                                    <option value="OK">لورم ایپسوم</option>
                                                    <option value="OR">اصفهان</option>
                                                    <option value="PA">لورم ایپسوم متن</option>
                                                    <option value="RI">لورم ایپسوم متن</option>
                                                    <option value="SC">لورم ایپسوم متن</option>
                                                    <option value="SD">لورم ایپسوم متن</option>
                                                    <option value="TN">لورم ایپسوم</option>
                                                    <option value="TX">تبریز</option>
                                                    <option value="UT">بندرعباس</option>
                                                    <option value="VT">لورم ایپسوم</option>
                                                    <option value="VA">لورم ایپسوم</option>
                                                    <option value="WA">رشت</option>
                                                    <option value="WV">لورم ایپسوم متن</option>
                                                    <option value="WI">لورم ایپسوم</option>
                                                    <option value="WY">کرمان</option>
                                                </select><span class="select2 select2-container select2-container--default" dir="rtl" data-select2-id="15" style="width: 394.3px;"><span class="selection"><span class="select2-selection select2-selection--single" role="combobox" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-disabled="false" aria-labelledby="select2-collapsible-state-container"><span class="select2-selection__rendered" id="select2-collapsible-state-container" role="textbox" aria-readonly="true"><span class="select2-selection__placeholder">انتخاب</span></span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label text-sm-end">نوع آدرس</label>
                                        <div class="col-sm-9 mt-2">
                                            <div class="form-check mb-2">
                                                <input name="collapsible-addressType" class="form-check-input" type="radio" value="" id="collapsible-addressType-home" checked="">
                                                <label class="form-check-label" for="collapsible-addressType-home">
                                                    منزل (تحویل کل روز)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input name="collapsible-addressType" class="form-check-input" type="radio" value="" id="collapsible-addressType-office">
                                                <label class="form-check-label" for="collapsible-addressType-office">
                                                    دفتر (تحویل بین 10 صبح - 5 عصر)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>

</div>
