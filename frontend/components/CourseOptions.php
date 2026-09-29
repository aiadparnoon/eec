<?php

namespace app\components;

use Yii;
use app\models\Brokers;
use app\models\Colleges;
use app\models\Courses;
use app\models\Lessons;
use app\models\Teachers;
use app\models\Users;
use yii\web\Response;

/**
 * گزینه‌ها و کمکی‌های مشترک فرم‌ها و فهرست دوره‌های کوتاه‌مدت و میان‌مدت (بر اساس دسترسی کاربر جاری).
 */
class CourseOptions
{
    /** واحدهای قابل مشاهده/فیلتر (کارشناس: واحد خود و واحدهای مرتبط) */
    public static function selectableUnits()
    {
        $titles = UsersDirectory::collegeTitles();
        if (CourseAccess::isAdmin())
            return $titles;
        $result = [];
        foreach (CourseAccess::units() as $id)
            if (isset($titles[$id]))
                $result[$id] = $titles[$id];
        return $result;
    }

    /** واحدهای قابل انتخاب در فرم ثبت: مدیر سیستم همه؛ بقیه فقط واحد خودشان */
    public static function creatableUnits()
    {
        $titles = UsersDirectory::collegeTitles();
        if (CourseAccess::canChooseUnit())
            return $titles;
        $result = [];
        foreach (CourseAccess::ownUnits() as $id)
            if (isset($titles[$id]))
                $result[$id] = $titles[$id];
        return $result;
    }

    public static function selectableBrokers()
    {
        if (CourseAccess::role() === 'broker')
            return [];
        $query = Brokers::find()->select(['connector_info', 'company_info', 'type'])->orderBy(['_id' => SORT_DESC]);
        if (!CourseAccess::isAdmin()) {
            $units = CourseAccess::units();
            $query->where(empty($units) ? ['_id' => null] : ['college' => $units]);
        }
        $result = [];
        foreach ($query->all() as $broker) {
            $ci = is_array($broker->connector_info) ? $broker->connector_info : [];
            $result[(string) $broker->_id] = trim((isset($ci['first_name']) ? $ci['first_name'] : '') . ' ' . (isset($ci['last_name']) ? $ci['last_name'] : ''));
        }
        return $result;
    }

    public static function selectableTeachers()
    {
        $query = Teachers::find()->select(['first_name', 'last_name'])->orderBy(['last_name' => SORT_ASC]);
        if (!CourseAccess::isAdmin()) {
            $units = CourseAccess::units();
            $query->where(empty($units) ? ['_id' => null] : ['colleges' => $units]);
        }
        $result = [];
        foreach ($query->all() as $teacher)
            $result[(string) $teacher->_id] = trim($teacher->first_name . ' ' . $teacher->last_name);
        return $result;
    }

    /**
     * «نوع ظرفیت» (بند ۶ صورتجلسه: «نامحدود» حذف). ظرفیت محدود فقط وقتی شناسه‌ی حساب واحد ثبت شده باشد.
     */
    public static function capacityTypes()
    {
        $types = ['2' => 'محدود', '3' => 'سازمانی'];
        if (CourseAccess::isAdmin())
            return $types;
        foreach (CourseAccess::units() as $id) {
            $unit = Colleges::findOne($id);
            if ($unit !== null && is_array($unit->financial_info) && !empty($unit->financial_info['id']))
                return $types;
        }
        return ['3' => 'سازمانی'];
    }

    /**
     * کارگزاران (فعال و دارای قرارداد)، مدرسان و دروس یک واحد برای فرم ثبت دوره.
     * واحد باید در دسترس کاربر باشد؛ کارگزار فقط خودش را می‌بیند.
     *
     * @return array ['brokers' => [...], 'teachers' => [...], 'lessons' => [...]]
     */
    public static function unitOptions($id)
    {
        if (!CourseAccess::canUseUnit($id))
            return ['brokers' => [], 'teachers' => [], 'lessons' => []];
        $brokers = [];
        $brokerQuery = Brokers::find()->where(['college' => $id, 'status' => '1']);
        if (CourseAccess::role() === 'broker') {
            $own = CourseAccess::broker();
            $brokerQuery->andWhere(['_id' => $own === null ? null : $own->_id]);
        }
        foreach ($brokerQuery->all() as $broker) {
            $ci = is_array($broker->connector_info) ? $broker->connector_info : [];
            $name = trim((isset($ci['first_name']) ? $ci['first_name'] : '') . ' ' . (isset($ci['last_name']) ? $ci['last_name'] : ''));
            if ((string) $broker->type === '1' && isset($broker->company_info['company_title']))
                $name .= ' (شرکت ' . $broker->company_info['company_title'] . ')';
            $contracts = [];
            foreach ((array) $broker->contracts as $contract)
                if (is_array($contract) && isset($contract['id']) && self::contractActive($contract))
                    $contracts[] =['id' => (string) $contract['id'], 'title' => (isset($contract['title']) ? (string) $contract['title'] : '') . (isset($contract['share']) ? ' (' . $contract['share'] . ' درصد)' : '')];
            if (empty($contracts))
                continue; // بدون قرارداد، دوره قابل ثبت نیست
            $brokers[] = ['id' => (string) $broker->_id, 'name' => $name !== '' ? $name : 'کارگزار بدون نام', 'contracts' => $contracts];
        }
        $teachers = [];
        foreach (Teachers::find()->select(['first_name', 'last_name'])->where(['colleges' => $id])->orderBy(['last_name' => SORT_ASC])->all() as $teacher)
            $teachers[] = ['id' => (string) $teacher->_id, 'name' => trim($teacher->first_name . ' ' . $teacher->last_name)];
        $lessons = [];
        foreach (Lessons::find()->select(['title'])->where(['college' => $id])->orderBy(['title' => SORT_ASC])->all() as $lesson) {
            $title = self::cleanTitle($lesson->title);
            if ($title !== '') // دروس بی‌عنوان (فقط فاصله) نمایش داده نمی‌شوند
                $lessons[] = ['id' => (string) $lesson->_id, 'name' => $title];
        }
        return ['brokers' => $brokers, 'teachers' => $teachers, 'lessons' => $lessons];
    }

    /** عنوان بدون فاصله/نیم‌فاصله‌ی ابتدا و انتها؛ عنوان غیرمتنی = خالی */
    public static function cleanTitle($title)
    {
        return is_string($title) ? trim(preg_replace('/^[\s\x{200C}\x{200F}\x{200E}]+|[\s\x{200C}\x{200F}\x{200E}]+$/u', '', $title)) : '';
    }

    /**
     * قرارداد کارگزار قابل انتخاب است اگر مدیر آن را غیرفعال نکرده باشد (status = '0').
     * قراردادهای قدیمی بدون فیلد وضعیت فعال حساب می‌شوند.
     */
    public static function contractActive(array $contract)
    {
        return !isset($contract['status']) || (string) $contract['status'] !== '0';
    }

    /**
     * کد مجوز = کد واحد + شمارنده‌ی سراسری (اتمیک).
     */
    public static function assignLicenseCode(Courses $model)
    {
        $unit = preg_match('/^[a-f0-9]{24}$/i', (string) $model->college) ? Colleges::findOne((string) $model->college) : null;
        if ($unit === null || !is_scalar($unit->prefix) || (string) $unit->prefix === '')
            return false;
        $doc = Yii::$app->mongodb->getCollection(['eec', 'generals'])
            ->findAndModify(['type' => 'license_code'], ['$inc' => ['data' => 1]], ['new' => true]);
        if (!isset($doc['data']))
            return false;
        $model->license_code = $unit->prefix . '-' . (int) $doc['data'];
        return true;
    }

    public static function namesById($class, array $ids, $field = null)
    {
        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id);
        })));
        if (empty($ids))
            return [];
        $result = [];
        foreach ($class::find()->where(['_id' => $ids])->all() as $model)
            $result[(string) $model->_id] = $field !== null ? (string) $model->$field : trim($model->first_name . ' ' . $model->last_name);
        return $result;
    }

    public static function brokerNames(array $ids)
    {
        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id);
        })));
        if (empty($ids))
            return [];
        $result = [];
        foreach (Brokers::find()->select(['connector_info'])->where(['_id' => $ids])->asArray()->all() as $broker) {
            $ci = isset($broker['connector_info']) && is_array($broker['connector_info']) ? $broker['connector_info'] : [];
            $result[(string) $broker['_id']] = trim((isset($ci['first_name']) ? $ci['first_name'] : '') . ' ' . (isset($ci['last_name']) ? $ci['last_name'] : ''));
        }
        return $result;
    }

    /** تعداد اعضای هر دوره با یک aggregate */
    public static function memberCounts(array $courseIds)
    {
        $courseIds = array_values(array_map('strval', $courseIds));
        if (empty($courseIds))
            return [];
        $result = [];
        foreach (Users::getCollection()->aggregate([
            ['$match' => ['courses._id' => ['$in' => $courseIds]]],
            ['$project' => ['courses._id' => 1]],
            ['$unwind' => '$courses'],
            ['$match' => ['courses._id' => ['$in' => $courseIds]]],
            ['$group' => ['_id' => '$courses._id', 'count' => ['$sum' => 1]]],
        ]) as $row)
            $result[(string) $row['_id']] = (int) $row['count'];
        return $result;
    }

    /** ارسال فایل اکسل ساخته‌شده و حذف آن پس از ارسال */
    public static function sendXlsx(XlsxWriter $writer, $name)
    {
        $path = Yii::getAlias('@runtime') . '/' . $name . '-' . bin2hex(random_bytes(6)) . '.xlsx';
        $writer->save($path);
        $response = Yii::$app->response->sendFile($path, $name . '-' . UsersDirectory::jdate('Y-m-d-H-i', time(), 'en') . '.xlsx');
        $response->on(Response::EVENT_AFTER_SEND, function () use ($path) {
            @unlink($path);
        });
        return $response;
    }
}
