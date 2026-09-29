<?php

namespace app\components\classroom;

use Yii;
use app\models\ClassroomServers;
use app\models\CourseSessions;
use app\models\Lessons;
use app\models\Principals;
use common\models\Admin;

/**
 * Adobe Connect از طریق وب‌سرویس واسط (api-eec) که آدرسش در تنظیمات سرور ذخیره شده است
 * (پیش‌فرض: @baseUrl). همه‌ی فراخوانی‌ها timeout دارند و در صورت خطا false برمی‌گردانند (نه خطای PHP).
 */
class AdobeConnectPlatform implements ClassroomPlatform
{
    const CONNECT_TIMEOUT = 10;
    const TIMEOUT = 15;

    /** @var ClassroomServers|null */
    protected $server;

    public function __construct(ClassroomServers $server = null)
    {
        $this->server = $server;
    }

    /** آدرس وب‌سرویس واسط */
    protected function apiUrl()
    {
        $url = $this->server !== null ? rtrim($this->server->setting('api_url'), '/') : '';
        return $url !== '' ? $url : Yii::getAlias('@baseUrl');
    }

    public function title()
    {
        return 'ادوبی کانکت';
    }

    public function registerCourseUsers($courseId)
    {
        $response = $this->call('/adobe-connect/add-users-courses/' . rawurlencode((string) $courseId));
        return $response !== null;
    }

    public function createCourseMeetings($courseId, $newLessonId = null)
    {
        $path = '/adobe-connect/create-meeting/' . rawurlencode((string) $courseId);
        if ($newLessonId !== null && $newLessonId !== '')
            $path .= '?new-lesson=' . rawurlencode((string) $newLessonId);
        $response = $this->call($path);
        if ($response === null)
            return null;
        $decoded = json_decode($response);
        return is_object($decoded) && isset($decoded->status) && $decoded->status == 'ok';
    }

    public function removeCourseUser($user, $courseId)
    {
        $response = $this->call('/adobe-connect/remove-course-user/' . rawurlencode((string) $user->_id) . '/' . rawurlencode((string) $courseId));
        return $response !== null;
    }

    public function updateLesson($courseId, $lessonId, $from, $to, $teacherAdminId = null)
    {
        $ok = function ($raw) {
            $decoded = $raw === null ? null : json_decode($raw);
            return is_object($decoded) && isset($decoded->status) && $decoded->status == 'ok';
        };
        $result = $ok($this->call('/adobe-connect/update-lesson', [
            'course_id' => (string) $courseId, 'lesson_id' => (string) $lessonId, 'from' => (string) $from, 'to' => (string) $to,
        ]));
        if ($result && $teacherAdminId !== null)
            $result = $ok($this->call('/adobe-connect/replace-teacher', [
                'course_id' => (string) $courseId, 'lesson_id' => (string) $lessonId, 'teacher_id' => (string) $teacherAdminId,
            ]));
        return $result;
    }

    public function deleteLesson($courseId, $lessonId)
    {
        $raw = $this->call('/adobe-connect/delete-lesson/' . rawurlencode((string) $courseId) . '/' . rawurlencode((string) $lessonId));
        $decoded = $raw === null ? null : json_decode($raw);
        return is_object($decoded) && isset($decoded->status) && $decoded->status == 'ok';
    }

    public function updateUser($user)
    {
        $response = $this->call('/adobe-connect/update-user-info', [
            'principal_id' => (string) $user->principal_id,
            'username' => (string) $user->username,
            'first_name' => (string) $user->first_name,
            'last_name' => (string) $user->last_name,
        ]);
        $decoded = $response === null ? null : json_decode($response);
        return is_object($decoded) && isset($decoded->status) && $decoded->status == 'ok';
    }

    public function attendance($user, $course)
    {
        $principalIds = $this->principalIds($user);
        $docs = CourseSessions::find()->where(['course_id' => (string) $course->_id])->all();
        if (empty($docs))
            return [];

        $lessonIds = [];
        foreach ($docs as $doc)
            if ($doc->lesson_id)
                $lessonIds[] = (string) $doc->lesson_id;
        $lessonTitles = [];
        if (!empty($lessonIds))
            foreach (Lessons::find()->where(['_id' => array_values(array_unique($lessonIds))])->all() as $lesson)
                $lessonTitles[(string) $lesson->_id] = $lesson->title;

        $rows = [];
        foreach ($docs as $doc) {
            if (!is_array($doc->sessions))
                continue;
            foreach ($doc->sessions as $session) {
                $enter = null;
                $exit = null;
                $seconds = 0;
                if (!empty($session['participants']) && !empty($principalIds)) {
                    foreach ($session['participants'] as $participant) {
                        if (!isset($participant['_id']) || !in_array((string) $participant['_id'], $principalIds, true))
                            continue;
                        $in = self::toTimestamp(isset($participant['enter']) ? $participant['enter'] : null);
                        $out = self::toTimestamp(isset($participant['exit']) ? $participant['exit'] : null);
                        if ($in !== null && ($enter === null || $in < $enter))
                            $enter = $in;
                        if ($out !== null && ($exit === null || $out > $exit))
                            $exit = $out;
                        if ($in !== null && $out !== null && $out > $in)
                            $seconds += $out - $in;
                    }
                }
                $rows[] = [
                    'lesson' => isset($lessonTitles[(string) $doc->lesson_id]) ? $lessonTitles[(string) $doc->lesson_id] : '-',
                    'from' => self::toTimestamp(isset($session['from']) ? $session['from'] : null),
                    'to' => self::toTimestamp(isset($session['to']) ? $session['to'] : null),
                    'present' => $enter !== null,
                    'enter' => $enter,
                    'exit' => $exit,
                    'minutes' => (int) round($seconds / 60),
                ];
            }
        }
        usort($rows, function ($a, $b) {
            return (int) $a['from'] - (int) $b['from'];
        });
        return $rows;
    }

    /**
     * principal_id های کاربر در ادوبی (از خود کاربر و از مجموعه‌ی principals).
     *
     * @return string[]
     */
    protected function principalIds($user)
    {
        $ids = [];
        if ($user->principal_id != null)
            $ids[] = (string) $user->principal_id;
        foreach (Principals::find()->where(['username' => (string) $user->username])->all() as $principal)
            if ($principal->principal_id != null)
                $ids[] = (string) $principal->principal_id;
        return array_values(array_unique($ids));
    }

    /**
     * فراخوانی POST وب‌سرویس. در صورت خطای شبکه/timeout/کد HTTP نامعتبر null برمی‌گرداند.
     *
     * @param string $path
     * @param array|null $json بدنه‌ی JSON
     * @return string|null
     */
    protected function call($path, $json = null)
    {
        $adminRole = Admin::find()->where(['role' => 'user'])->one();
        if ($adminRole == null)
            return null;

        $headers = ['_id: ' . (string) $adminRole->_id];
        $options = [
            CURLOPT_URL => $this->apiUrl() . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ];
        if ($json !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
        }
        $options[CURLOPT_HTTPHEADER] = $headers;

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl);
        $failed = $raw === false || curl_errno($curl) !== 0;
        $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($failed || $code >= 400) {
            Yii::warning('Adobe Connect call failed: ' . $path . ' (HTTP ' . $code . ')', __METHOD__);
            return null;
        }
        return (string) $raw;
    }

    /**
     * تاریخ ذخیره‌شده (رشته‌ی ISO، UTCDateTime یا عدد) را به timestamp تبدیل می‌کند.
     *
     * @return int|null
     */
    public static function toTimestamp($value)
    {
        if ($value === null || $value === '')
            return null;
        if ($value instanceof \MongoDB\BSON\UTCDateTime)
            return (int) floor(((int) (string) $value) / 1000);
        if (is_numeric($value))
            return (int) $value;
        $ts = strtotime((string) $value);
        return $ts === false ? null : $ts;
    }
}
