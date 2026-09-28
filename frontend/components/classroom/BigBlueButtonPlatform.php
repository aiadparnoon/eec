<?php

namespace app\components\classroom;

use Yii;
use app\models\ClassroomServers;
use app\models\Courses;

/**
 * BigBlueButton از طریق API رسمی آن (checksum = hash(call + query + secret)).
 *
 * جلسه‌های BBB موقتی‌اند و اگر کسی وارد نشود پس از چند دقیقه حذف می‌شوند؛ پس «ساخت کلاس» برای دوره
 * فقط شناسه و رمزهای جلسه‌ی هر درس را در دوره (classroom_meetings) ذخیره و دسترسی به سرور را بررسی می‌کند.
 * فراخوانی create (که تکرارش بی‌خطر است) درست پیش از ورود، در joinUrl() انجام می‌شود.
 */
class BigBlueButtonPlatform implements ClassroomPlatform
{
    const TIMEOUT = 15;

    /** @var ClassroomServers */
    protected $server;

    public function __construct(ClassroomServers $server)
    {
        $this->server = $server;
    }

    public function title()
    {
        return 'بیگ‌بلوباتن';
    }

    /** BBB کاربر از پیش ثبت‌شده ندارد؛ ورود با لینک امضاشده انجام می‌شود */
    public function registerCourseUsers($courseId)
    {
        return true;
    }

    public function createCourseMeetings($courseId, $newLessonId = null)
    {
        $course = Courses::findOne($courseId);
        if ($course === null)
            return false;
        if (!$this->ping())
            return null;
        $meetings = is_array($course->classroom_meetings) ? $course->classroom_meetings : [];
        foreach ((array) $course->lessons as $lesson) {
            if (!is_array($lesson) || empty($lesson['_id']))
                continue;
            $lessonId = (string) $lesson['_id'];
            if (isset($meetings[$lessonId]['server']) && $meetings[$lessonId]['server'] === (string) $this->server->_id)
                continue;
            $meetings[$lessonId] = [
                'server' => (string) $this->server->_id,
                'meeting_id' => 'eec-' . (string) $course->_id . '-' . $lessonId,
                'attendee_pw' => Yii::$app->security->generateRandomString(16),
                'moderator_pw' => Yii::$app->security->generateRandomString(16),
            ];
        }
        $course->classroom_meetings = $meetings;
        return $course->save(false, ['classroom_meetings']);
    }

    public function removeCourseUser($user, $courseId)
    {
        return true;
    }

    public function updateUser($user)
    {
        return true;
    }

    /** حضور و غیاب BBB از طریق وب‌هوک/Learning Analytics ثبت می‌شود؛ هنوز پیاده نشده */
    public function attendance($user, $course)
    {
        return [];
    }

    /**
     * لینک ورود به کلاس یک درس (جلسه در صورت نیاز ساخته می‌شود).
     *
     * @return string|null
     */
    public function joinUrl(Courses $course, $lessonId, $fullName, $moderator = false, $userId = null)
    {
        $meetings = is_array($course->classroom_meetings) ? $course->classroom_meetings : [];
        if (!isset($meetings[(string) $lessonId]))
            return null;
        $meeting = $meetings[(string) $lessonId];
        $create = [
            'name' => is_array($course->title) && isset($course->title['main_fa']) ? (string) $course->title['main_fa'] : 'کلاس',
            'meetingID' => $meeting['meeting_id'],
            'attendeePW' => $meeting['attendee_pw'],
            'moderatorPW' => $meeting['moderator_pw'],
            'record' => $this->server->setting('record', '1') === '1' ? 'true' : 'false',
        ];
        $max = (int) $this->server->setting('max_participants', '0');
        if ($max > 0)
            $create['maxParticipants'] = (string) $max;
        $xml = $this->request('create', $create);
        if ($xml === null || (string) $xml->returncode !== 'SUCCESS')
            return null;
        $join = [
            'fullName' => (string) $fullName,
            'meetingID' => $meeting['meeting_id'],
            'password' => $moderator ? $meeting['moderator_pw'] : $meeting['attendee_pw'],
            'redirect' => 'true',
        ];
        if ($userId !== null)
            $join['userID'] = (string) $userId;
        return $this->url('join', $join);
    }

    /** بررسی دسترسی و صحت کلید: getMeetings فقط با checksum درست SUCCESS برمی‌گرداند */
    public function ping()
    {
        $xml = $this->request('getMeetings', []);
        return $xml !== null && (string) $xml->returncode === 'SUCCESS';
    }

    protected function url($call, array $params)
    {
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $algo = $this->server->setting('checksum', 'sha1');
        if (!in_array($algo, ['sha1', 'sha256', 'sha512'], true))
            $algo = 'sha1';
        $checksum = hash($algo, $call . $query . $this->server->setting('secret'));
        $base = rtrim($this->server->setting('url'), '/');
        if (substr($base, -4) !== '/api')
            $base .= '/api';
        return $base . '/' . $call . '?' . ($query === '' ? '' : $query . '&') . 'checksum=' . $checksum;
    }

    /**
     * @return \SimpleXMLElement|null
     */
    protected function request($call, array $params)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->url($call, $params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $raw = curl_exec($curl);
        $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($raw === false || $code >= 400) {
            Yii::warning('BigBlueButton call failed: ' . $call . ' (HTTP ' . $code . ')', __METHOD__);
            return null;
        }
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) $raw, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($previous);
        return $xml === false ? null : $xml;
    }
}
