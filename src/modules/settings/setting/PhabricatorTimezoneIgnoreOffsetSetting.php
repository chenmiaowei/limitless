<?php

namespace orangins\modules\settings\setting;

/**
 * Class PhabricatorTimezoneIgnoreOffsetSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorTimezoneIgnoreOffsetSetting extends PhabricatorInternalSetting
{

    /**
     *
     */
    const SETTINGKEY = 'time_offset_ignore';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Timezone Ignored Offset');
    }
}
