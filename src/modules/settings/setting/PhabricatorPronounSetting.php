<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorAccountSettingsPanel;
use PhutilPerson;

/**
 * Class PhabricatorPronounSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorPronounSetting extends PhabricatorSelectSetting
{
    /**
     *
     */
    const SETTINGKEY = 'pronoun';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Pronoun');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorAccountSettingsPanel::PANELKEY;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getSettingOrder()
    {
        return 200;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getControlInstructions()
    {
        return \Yii::t("app", 'Choose the pronoun you prefer.');
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return PhutilPerson::GENDER_UNKNOWN;
    }

    /**
     * @return array|mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        // TODO: When editing another user's settings as an administrator, this
        // is not the best username: the user's username would be better.

        $viewer = $this->getViewer();
        $username = $viewer->username;

        $label_unknown = \Yii::t("app", '{0} updated their profile', [$username]);
        $label_her = \Yii::t("app", '{0} updated her profile', [$username]);
        $label_his = \Yii::t("app", '{0} updated his profile', [$username]);

        return array(
            PhutilPerson::GENDER_UNKNOWN => $label_unknown,
            PhutilPerson::GENDER_MASCULINE => $label_his,
            PhutilPerson::GENDER_FEMININE => $label_her,
        );
    }

}
