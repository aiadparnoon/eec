<?php

namespace app\components;

use Yii;

/**
 * آمار واحدها (مجموعه‌ی colleges) با یک aggregation برای هر مجموعه، نه یک کوئری برای هر واحد.
 * فیلدهای college ممکن است رشته یا آرایه باشند؛ $unwind هر دو را پوشش می‌دهد.
 */
class UnitStats
{
    const FIELDS = [
        'students' => 'دانشپذیر',
        'staff' => 'کارمند',
        'teachers' => 'استاد',
        'courses' => 'دوره',
        'activeCourses' => 'دوره فعال',
        'certificates' => 'مدرک صادرشده',
        'pendingCertificates' => 'درخواست مدرک در انتظار',
        'brokers' => 'کارگزار',
        'activeBrokers' => 'کارگزار فعال',
    ];

    /**
     * @param string[]|null $unitIds محدود به این واحدها (null = همه)
     * @return array [unitId => [field => count]] و کلید '_total' برای جمع کل
     */
    public static function all($unitIds = null)
    {
        $stats = [];
        $add = function ($id, $field, $count) use (&$stats, $unitIds) {
            $id = is_scalar($id) || $id instanceof \MongoDB\BSON\ObjectId ? trim((string) $id) : '';
            if ($id === '' || ($unitIds !== null && !in_array($id, $unitIds, true)))
                return;
            if (!isset($stats[$id]))
                $stats[$id] = array_fill_keys(array_keys(self::FIELDS), 0);
            $stats[$id][$field] += (int) $count;
        };

        foreach (self::group('users', 'college') as $row)
            $add($row['_id'], 'students', $row['count']);
        foreach (self::group('admin', 'college', ['role' => 'emp']) as $row)
            $add($row['_id'], 'staff', $row['count']);
        foreach (self::group('teachers', 'colleges') as $row)
            $add($row['_id'], 'teachers', $row['count']);
        foreach (self::group('courses', 'college', [], ['active' => ['$sum' => ['$cond' => [['$eq' => ['$status', '1']], 1, 0]]]]) as $row) {
            $add($row['_id'], 'courses', $row['count']);
            $add($row['_id'], 'activeCourses', $row['active']);
        }
        foreach (self::group('certificate_requests', 'college', [], [
            'issued' => ['$sum' => ['$cond' => [['$eq' => ['$status', '4']], 1, 0]]],
            'pending' => ['$sum' => ['$cond' => [['$in' => ['$status', ['1', '2']]], 1, 0]]],
        ]) as $row) {
            $add($row['_id'], 'certificates', $row['issued']);
            $add($row['_id'], 'pendingCertificates', $row['pending']);
        }
        foreach (self::group('brokers', 'college', [], ['active' => ['$sum' => ['$cond' => [['$eq' => ['$status', '1']], 1, 0]]]]) as $row) {
            $add($row['_id'], 'brokers', $row['count']);
            $add($row['_id'], 'activeBrokers', $row['active']);
        }

        $total = array_fill_keys(array_keys(self::FIELDS), 0);
        foreach ($stats as $row)
            foreach ($row as $field => $count)
                $total[$field] += $count;
        // دانشپذیر عضو چند واحد، در جمع کل فقط یک بار شمرده شود
        $total['students'] = self::distinctCount('users', 'college', $unitIds);
        $stats['_total'] = $total;
        return $stats;
    }

    public static function empty()
    {
        return array_fill_keys(array_keys(self::FIELDS), 0);
    }

    /**
     * @return array[] [['_id' => واحد, 'count' => n, ...extra]]
     */
    private static function group($collection, $field, array $match = [], array $extra = [])
    {
        $pipeline = [];
        if (!empty($match))
            $pipeline[] = ['$match' => $match];
        $project = [$field => 1];
        if (!empty($extra))
            $project['status'] = 1; // شمارش‌های شرطی بر اساس status
        $pipeline[] = ['$project' => $project];
        $pipeline[] = ['$unwind' => '$' . $field];
        $pipeline[] = ['$group' => array_merge(['_id' => '$' . $field, 'count' => ['$sum' => 1]], $extra)];
        try {
            return Yii::$app->mongodb->getCollection(['eec', $collection])->aggregate($pipeline);
        } catch (\Exception $e) {
            Yii::warning('Unit stats aggregation failed for ' . $collection . ': ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * تعداد سندهای یکتایی که حداقل عضو یکی از واحدهای داده‌شده هستند.
     */
    private static function distinctCount($collection, $field, $unitIds)
    {
        $condition = $unitIds === null
            ? [$field => ['$nin' => [null, '', []]]]
            : [$field => ['$in' => array_values($unitIds)]];
        try {
            return (int) Yii::$app->mongodb->getCollection(['eec', $collection])->count($condition);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
