<?php

namespace orangins\modules\file\keyring;

use orangins\lib\OranginsObject;
use orangins\lib\env\PhabricatorEnv;

/**
 * Class PhabricatorKeyring
 * @package orangins\modules\file\keyring
 * @author 陈妙威
 */
final class PhabricatorKeyring extends OranginsObject
{
    /**
     * @var
     */
    private static $hasReadConfiguration;
    /**
     * @var array
     */
    private static $keyRing = array();

    /**
     * @param $spec
     * @author 陈妙威
     */
    public static function addKey($spec)
    {
        self::$keyRing[$spec['name']] = $spec;
    }

    /**
     * @param $name
     * @param $type
     * @return PhutilOpaqueEnvelope
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public static function getKey($name, $type)
    {
        self::readConfiguration();

        if (empty(self::$keyRing[$name])) {
            throw new Exception(
                \Yii::t("app",
                    'No key "%s" exists in keyring.',
                    $name));
        }

        $spec = self::$keyRing[$name];

        $material = base64_decode($spec['material.base64'], true);
        return new PhutilOpaqueEnvelope($material);
    }

    /**
     * @param $type
     * @return int|null|string
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public static function getDefaultKeyName($type)
    {
        self::readConfiguration();

        foreach (self::$keyRing as $name => $key) {
            if (!empty($key['default'])) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private static function readConfiguration()
    {
        if (self::$hasReadConfiguration) {
            return true;
        }

        self::$hasReadConfiguration = true;

        foreach (PhabricatorEnv::getEnvConfig('keyring') as $spec) {
            self::addKey($spec);
        }
    }

}
