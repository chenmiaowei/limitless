<?php

namespace orangins\modules\cache\spec;

use orangins\lib\env\PhabricatorEnv;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDataCacheSpec
 * @package orangins\modules\cache\spec
 * @author 陈妙威
 */
final class PhabricatorDataCacheSpec extends PhabricatorCacheSpec
{

    /**
     * @var
     */
    private $cacheSummary;

    /**
     * @param array $cache_summary
     * @return $this
     * @author 陈妙威
     */
    public function setCacheSummary(array $cache_summary)
    {
        $this->cacheSummary = $cache_summary;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCacheSummary()
    {
        return $this->cacheSummary;
    }

    /**
     * @return PhabricatorDataCacheSpec
     * @author 陈妙威
     */
    public static function getActiveCacheSpec()
    {
        $spec = new PhabricatorDataCacheSpec();

        // NOTE: If APCu is installed, it reports that APC is installed.
        if (extension_loaded('apc') && !extension_loaded('apcu')) {
            $spec->initAPCSpec();
        } else if (extension_loaded('apcu')) {
            $spec->initAPCuSpec();
        } else {
            $spec->initNoneSpec();
        }

        return $spec;
    }

    /**
     * @author 陈妙威
     */
    private function initAPCSpec()
    {
        $this
            ->setName(\Yii::t("app", 'APC User Cache'))
            ->setVersion(phpversion('apc'));

        if (ini_get('apc.enabled')) {
            $this
                ->setIsEnabled(true)
                ->setClearCacheCallback('apc_clear_cache');
            $this->initAPCCommonSpec();
        } else {
            $this->setIsEnabled(false);
            $this->raiseEnableAPCIssue();
        }
    }

    /**
     * @author 陈妙威
     */
    private function initAPCuSpec()
    {
        $this
            ->setName(\Yii::t("app", 'APCu'))
            ->setVersion(phpversion('apcu'));

        if (ini_get('apc.enabled')) {
            if (function_exists('apcu_clear_cache')) {
                $clear_callback = 'apcu_clear_cache';
            } else {
                $clear_callback = 'apc_clear_cache';
            }

            $this
                ->setIsEnabled(true)
                ->setClearCacheCallback($clear_callback);
            $this->initAPCCommonSpec();
        } else {
            $this->setIsEnabled(false);
            $this->raiseEnableAPCIssue();
        }
    }

    /**
     * @author 陈妙威
     */
    private function initNoneSpec()
    {
        if (version_compare(phpversion(), '5.5', '>=')) {
            $message = \Yii::t("app",
                'Installing the "APCu" PHP extension will improve performance. ' .
                'This extension is strongly recommended. Without it, Phabricator ' .
                'must rely on a very inefficient disk-based cache.');

            $this
                ->newIssue('extension.apcu')
                ->setShortName(\Yii::t("app", 'APCu'))
                ->setName(\Yii::t("app", 'PHP Extension "APCu" Not Installed'))
                ->setMessage($message)
                ->addPHPExtension('apcu');
        } else {
            $this->raiseInstallAPCIssue();
        }
    }

    /**
     * @author 陈妙威
     */
    private function initAPCCommonSpec()
    {
        $state = array();

        if (function_exists('apcu_sma_info')) {
            $mem = apcu_sma_info();
            $info = apcu_cache_info();
        } else if (function_exists('apc_sma_info')) {
            $mem = apc_sma_info();
            $info = apc_cache_info('user');
        } else {
            $mem = null;
        }

        if ($mem) {
            $this->setTotalMemory($mem['num_seg'] * $mem['seg_size']);

            $this->setUsedMemory($info['mem_size']);
            $this->setEntryCount(count($info['cache_list']));

            $cache = $info['cache_list'];
            $state = array();
            foreach ($cache as $item) {
                // Some older versions of APCu report the cachekey as "key", while
                // newer APCu and APC report it as "info". Just check both indexes
                // for commpatibility. See T13164 for details.

                $info = ArrayHelper::getValue($item, 'info');
                if ($info === null) {
                    $info = ArrayHelper::getValue($item, 'key');
                }

                if ($info === null) {
                    $key = '<unknown-key>';
                } else {
                    $key = self::getKeyPattern($info);
                }

                if (empty($state[$key])) {
                    $state[$key] = array(
                        'max' => 0,
                        'total' => 0,
                        'count' => 0,
                    );
                }
                $state[$key]['max'] = max($state[$key]['max'], $item['mem_size']);
                $state[$key]['total'] += $item['mem_size'];
                $state[$key]['count']++;
            }
        }

        $this->setCacheSummary($state);
    }

    /**
     * @param $key
     * @return null|string|string[]
     * @throws \Exception
     * @author 陈妙威
     */
    private static function getKeyPattern($key)
    {
        // If this key isn't in the current cache namespace, don't reveal any
        // information about it.
        $namespace = PhabricatorEnv::getEnvConfig('phabricator.cache-namespace');
        if (strncmp($key, $namespace . ':', strlen($namespace) + 1)) {
            return '<other-namespace>';
        }

        $key = preg_replace('/(?<![a-zA-Z])\d+(?![a-zA-Z])/', 'N', $key);
        $key = preg_replace('/PHID-[A-Z]{4}-[a-z0-9]{20}/', 'PHID', $key);

        // TODO: We should probably standardize how digests get embedded into cache
        // keys to make this rule more generic.
        $key = preg_replace('/:celerity:.*$/', ':celerity:X', $key);
        $key = preg_replace('/:pkcs8:.*$/', ':pkcs8:X', $key);

        return $key;
    }
}
