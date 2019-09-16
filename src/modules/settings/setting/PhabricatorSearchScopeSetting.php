<?php

namespace orangins\modules\settings\setting;

/**
 * Class PhabricatorSearchScopeSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorSearchScopeSetting extends PhabricatorInternalSetting
{

    /**
     *
     */
    const SETTINGKEY = 'search-scope';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Search Scope');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return 'all';
    }

}
