<?php

namespace orangins\modules\people\cache;

use orangins\lib\helpers\OranginsUtil;
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
final class PhabricatorUserPreferencesCacheType
    extends PhabricatorUserCacheType
{

    /**
     *
     */
    const CACHETYPE = 'preferences';

    /**
     *
     */
    const KEY_PREFERENCES = 'user.preferences.v2';

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
     * @param array $users
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function newValueForUsers($key, array $users)
    {
        $viewer = $this->getViewer();

        $users =  mpull($users, null, 'getPHID');
        $user_phids = array_keys($users);

        $preferences = PhabricatorUserPreferences::find()
            ->setViewer($viewer)
            ->withUsers($users)
            ->needSyntheticPreferences(true)
            ->execute();
        $preferences = mpull($preferences, null, 'getUserPHID');

        $all_settings = PhabricatorSetting::getAllSettings();

        $settings = array();
        foreach ($users as $user_phid => $user) {
            $preference = ArrayHelper::getValue($preferences, $user_phid);

            if (!$preference) {
                continue;
            }

            foreach ($all_settings as $key => $setting) {
                $value = $preference->getSettingValue($key);

                $phabricatorSetting = clone $setting;
                try {
                    $phabricatorSetting
                        ->setViewer($viewer)
                        ->assertValidValue($value);
                } catch (Exception $ex) {
                    // If the saved value isn't valid, don't cache it: we'll use the
                    // default value instead.
                    continue;
                }

                // As an optimization, we omit the value from the cache if it is
                // exactly the same as the hardcoded default.
                $default_value = $phabricatorSetting
                    ->setViewer($user)
                    ->getSettingDefaultValue();
                if ($value === $default_value) {
                    continue;
                }

                $settings[$user_phid][$key] = $value;
            }
        }

        $results = array();
        foreach ($user_phids as $user_phid) {
            $value = ArrayHelper::getValue($settings, $user_phid, array());
            $results[$user_phid] = Json::encode($value);
        }

        return $results;
    }

}
