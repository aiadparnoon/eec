<?php

use yii\mongodb\Migration;

/**
 * users.college از رشته به آرایه (docs/specs/users-manage.md بند ۲).
 *
 *  - رشته‌ی غیرخالی (یا ObjectId) → [مقدار]
 *  - null، رشته‌ی خالی یا نبودِ فیلد → []
 *
 * فقط سندهایی که college آن‌ها آرایه نیست تغییر می‌کنند؛ پس اجرای دوباره بی‌اثر است (idempotent).
 *
 * اجرا: php yii mongodb-migrate
 */
class m260927_100000_users_college_to_array extends Migration
{
    public function up()
    {
        $collection = Yii::$app->mongodb->getCollection(['eec', 'users']);
        $cursor = $collection->find(['college' => ['$not' => ['$type' => 'array']]], ['_id' => 1, 'college' => 1]);
        $converted = 0;
        $emptied = 0;
        foreach ($cursor as $doc) {
            $value = array_key_exists('college', $doc) ? $doc['college'] : null;
            $value = $value === null ? '' : trim((string) $value);
            $collection->update(['_id' => $doc['_id']], ['$set' => ['college' => $value === '' ? [] : [$value]]]);
            if ($value === '')
                $emptied++;
            else
                $converted++;
        }
        echo "    > users.college: $converted converted to [value], $emptied set to []\n";
        return true;
    }

    public function down()
    {
        $collection = Yii::$app->mongodb->getCollection(['eec', 'users']);
        $restored = 0;
        foreach ($collection->find(['college' => ['$type' => 'array']], ['_id' => 1, 'college' => 1]) as $doc) {
            $values = (array) $doc['college'];
            $collection->update(['_id' => $doc['_id']], ['$set' => ['college' => count($values) ? (string) reset($values) : null]]);
            $restored++;
        }
        echo "    > users.college: $restored restored to a single value (extra colleges are dropped)\n";
        return true;
    }
}
