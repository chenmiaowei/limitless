<?php

namespace orangins\modules\settings\setting;

/**
 * Class PhabricatorEmailTagsSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEmailTagsSetting
    extends PhabricatorInternalSetting
{

    /**
     *
     */
    const SETTINGKEY = 'mailtags';

    // These are in an unusual order for historic reasons.
    /**
     *
     */
    const VALUE_NOTIFY = 0;
    /**
     *
     */
    const VALUE_EMAIL = 1;
    /**
     *
     */
    const VALUE_IGNORE = 2;

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Mail Tags');
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
