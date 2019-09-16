<?php

namespace orangins\modules\cache;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\cache\models\PhabricatorCacheGeneral;
use orangins\modules\cache\models\PhabricatorMarkupCache;
use PhutilKeyValueCache;
use Yii;
use Exception;

/**
 * Class PhabricatorKeyValueDatabaseCache
 * @package orangins\modules\cache
 * @author 陈妙威
 */
final class PhabricatorKeyValueDatabaseCache
    extends PhutilKeyValueCache
{

    /**
     *
     */
    const CACHE_FORMAT_RAW = 'raw';
    /**
     *
     */
    const CACHE_FORMAT_DEFLATE = 'deflate';

    /**
     * @param array $keys
     * @param null $ttl
     * @return $this|PhutilKeyValueCache|void
     * @author 陈妙威
     * @throws Exception
     */
    public function setKeys(array $keys, $ttl = null)
    {
        if (PhabricatorEnv::isReadOnly()) {
            return;
        }

        if ($keys) {
            $map = $this->digestKeys(array_keys($keys));

            $sql = array();
            foreach ($map as $key => $hash) {
                $value = $keys[$key];

                list($format, $storage_value) = $this->willWriteValue($key, $value);

//                $sql[] = qsprintf(
//                    $conn_w,
//                    '(%s, %s, %s, %B, %d, %nd)',
//                    $hash,
//                    $key,
//                    $format,
//                    $storage_value,
//                    time(),
//                    $ttl ? (time() + $ttl) : null);


                $arr = [
                    'cache_key_hash' => $hash,
                    'cache_key' => $key,
                    'cache_format' => $format,
                    'cache_data' => $storage_value,
                    'created_at' => time(),
                    'cache_expires' => $ttl ? (time() + $ttl) : null
                ];

                PhabricatorCacheGeneral::getDb()->createCommand()->upsert(PhabricatorCacheGeneral::tableName(), $arr, [
                    'cache_key' => new \yii\db\Expression('VALUES(cache_key)'),
                    'cache_format' => new \yii\db\Expression('VALUES(cache_format)'),
                    'cache_data' => new \yii\db\Expression('VALUES(cache_data)'),
                    'created_at' => new \yii\db\Expression('VALUES(created_at)'),
                    'cache_expires' => new \yii\db\Expression('VALUES(cache_expires)'),
                ])->execute();
            }

//            $guard = AphrontWriteGuard::beginScopedUnguardedWrites();
//            foreach (PhabricatorLiskDAO::chunkSQL($sql) as $chunk) {
//                queryfx(
//                    $conn_w,
//                    'INSERT INTO %T
//              (cacheKeyHash, cacheKey, cache_format, cache_data,
//                cacheCreated, cache_expires) VALUES %LQ
//              ON DUPLICATE KEY UPDATE
//                cacheKey = VALUES(cacheKey),
//                cache_format = VALUES(cache_format),
//                cache_data = VALUES(cache_data),
//                cacheCreated = VALUES(cacheCreated),
//                cache_expires = VALUES(cache_expires)',
//                    $this->getTableName(),
//                    $chunk);
//            }
//            unset($guard);
        }

        return $this;
    }

    /**
     * @param array $keys
     * @return array
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function getKeys(array $keys)
    {
        $results = array();
        if ($keys) {
            $map = $this->digestKeys($keys);


            $rows = PhabricatorCacheGeneral::find()->where(['IN', 'cache_key_hash', $map])->all();
            $rows = ipull($rows, null, 'cache_key');

            foreach ($keys as $key) {
                if (empty($rows[$key])) {
                    continue;
                }

                $row = $rows[$key];

                if ($row['cache_expires'] && ($row['cache_expires'] < time())) {
                    continue;
                }

                try {
                    $results[$key] = $this->didReadValue(
                        $row['cache_format'],
                        $row['cache_data']);
                } catch (Exception $ex) {
                    // Treat this as a cache miss.
                    Yii::error($ex);
                }
            }
        }

        return $results;
    }

    /**
     * @param array $keys
     * @return $this|PhutilKeyValueCache
     * @author 陈妙威
     */
    public function deleteKeys(array $keys)
    {
        if ($keys) {
            $map = $this->digestKeys($keys);
            PhabricatorCacheGeneral::deleteAll([
               'IN',
                'cache_key_hash',
                $map
            ]);
        }
        return $this;
    }

    /**
     * @return $this|PhutilKeyValueCache
     * @author 陈妙威
     */
    public function destroyCache()
    {
        PhabricatorCacheGeneral::deleteAll(1);
        return $this;
    }


    /* -(  Raw Cache Access  )--------------------------------------------------- */

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTableName()
    {
        return 'cache_general';
    }


    /* -(  Implementation  )----------------------------------------------------- */


    /**
     * @param array $keys
     * @return array
     * @author 陈妙威
     */
    private function digestKeys(array $keys)
    {
        $map = array();
        foreach ($keys as $key) {
            $map[$key] = PhabricatorHash::digestForIndex($key);
        }
        return $map;
    }

    /**
     * @param $key
     * @param $value
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    private function willWriteValue($key, $value)
    {
        if (!is_string($value)) {
            throw new Exception(\Yii::t("app",'Only strings may be written to the DB cache!'));
        }

        static $can_deflate;
        if ($can_deflate === null) {
            $can_deflate = function_exists('gzdeflate') &&
                PhabricatorEnv::getEnvConfig('cache.enable-deflate');
        }

        if ($can_deflate) {
            $deflated = PhabricatorCaches::maybeDeflateData($value);
            if ($deflated !== null) {
                return array(self::CACHE_FORMAT_DEFLATE, $deflated);
            }
        }

        return array(self::CACHE_FORMAT_RAW, $value);
    }

    /**
     * @param $format
     * @param $value
     * @return string
     * @author 陈妙威
     * @throws Exception
     */
    private function didReadValue($format, $value)
    {
        switch ($format) {
            case self::CACHE_FORMAT_RAW:
                return $value;
            case self::CACHE_FORMAT_DEFLATE:
                return PhabricatorCaches::inflateData($value);
            default:
                throw new Exception(\Yii::t("app",'Unknown cache format.'));
        }
    }


}
