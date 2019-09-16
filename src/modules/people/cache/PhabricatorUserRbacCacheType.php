<?php

namespace orangins\modules\people\cache;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\rbac\models\RbacUser;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\setting\PhabricatorSetting;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class PhabricatorUserPreferencesCacheType
 * @package orangins\modules\people\cache
 * @author 陈妙威
 */
final class PhabricatorUserRbacCacheType extends PhabricatorUserCacheType
{

    /**
     *
     */
    const CACHETYPE = 'rbac';

    /**
     *
     */
    const KEY_PREFERENCES = 'user.rbac.v1';

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAutoloadKeys()
    {
        return array(
            self::KEY_PREFERENCES,
        );
    }

    /**
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    public function canManageKey($key)
    {
        return ($key === self::KEY_PREFERENCES);
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getValueFromStorage($value)
    {
        return Json::decode($value);
    }

    /**
     * @param $key
     * @param PhabricatorUser[] $users
     * @return array
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function newValueForUsers($key, array $users)
    {
        /** @var PhabricatorUser[] $users */
        $users = mpull($users, null, 'getPHID');
        $user_phids = array_keys($users);

        $settings = array();
        foreach ($users as $user_phid => $user) {

            /** @var  RbacUser[] $rbacUsers */
            $rbacUsers = RbacUser::find()->andWhere(['user_phid' => $user->getPHID()])->all();
            $value = [
                'user.nodes' => ArrayHelper::getColumn($rbacUsers, 'object_phid'),
            ];
            $settings[$user_phid] = $value;
        }

        $results = array();
        foreach ($user_phids as $user_phid) {
            $value = ArrayHelper::getValue($settings, $user_phid, array());
            $results[$user_phid] = Json::encode($value);
        }

        return $results;
    }
}
