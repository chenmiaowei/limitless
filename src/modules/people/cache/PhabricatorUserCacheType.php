<?php

namespace orangins\modules\people\cache;

use orangins\lib\OranginsObject;
use PhutilMethodNotImplementedException;
use PhutilClassMapQuery;
use orangins\modules\people\models\PhabricatorUser;
use Exception;

/**
 * Class PhabricatorUserCacheType
 * @package orangins\modules\people\cache
 * @author 陈妙威
 */
abstract class PhabricatorUserCacheType extends OranginsObject
{

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return PhabricatorUser::getOmnipotentUser();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAutoloadKeys()
    {
        return array();
    }

    /**
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    public function canManageKey($key)
    {
        return false;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDefaultValue()
    {
        return array();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldValidateRawCacheData()
    {
        return false;
    }

    /**
     * @param PhabricatorUser $user
     * @param $key
     * @param $data
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function isRawCacheDataValid(PhabricatorUser $user, $key, $data)
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getValueFromStorage($value)
    {
        return $value;
    }

    /**
     * @param $key
     * @param array $users
     * @return array
     * @author 陈妙威
     */
    public function newValueForUsers($key, array $users)
    {
        return array();
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getUserCacheType()
    {
        return $this->getPhobjectClassConstant('CACHETYPE');
    }

    /**
     * @return PhabricatorUserCacheType[]
     * @author 陈妙威
     */
    public static function getAllCacheTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorUserCacheType::class)
            ->setUniqueMethod('getUserCacheType')
            ->execute();
    }

    /**
     * @param $key
     * @return PhabricatorUserCacheType
     * @author 陈妙威
     */
    public static function getCacheTypeForKey($key)
    {
        $all = self::getAllCacheTypes();
        foreach ($all as $type) {
            if ($type->canManageKey($key)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param $key
     * @return PhabricatorUserCacheType
     * @author 陈妙威
     * @throws Exception
     */
    public static function requireCacheTypeForKey($key)
    {
        $type = self::getCacheTypeForKey($key);

        if (!$type) {
            throw new Exception(
                \Yii::t("app",
                    'Failed to load UserCacheType to manage key "{0}". This cache type ' .
                    'is required.', [
                        $key
                    ]));
        }

        return $type;
    }

}
