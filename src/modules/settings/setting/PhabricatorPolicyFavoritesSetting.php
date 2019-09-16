<?php

namespace orangins\modules\settings\setting;

use Yii;

/**
 * Class PhabricatorPolicyFavoritesSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorPolicyFavoritesSetting
    extends PhabricatorInternalSetting
{

    /**
     *
     */
    const SETTINGKEY = 'policy.favorites';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return Yii::t('app', 'Policy Favorites');
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return array();
    }
}
