<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorEmailFormatSettingsPanel;

/**
 * Class PhabricatorEmailStampsSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEmailStampsSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'stamps';

    /**
     *
     */
    const VALUE_BODY_STAMPS = 'body';
    /**
     *
     */
    const VALUE_HEADER_STAMPS = 'header';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Send Stamps');
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorEmailFormatSettingsPanel::PANELKEY;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getSettingOrder()
    {
        return 400;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",<<<EOREMARKUP
Phabricator stamps mail with labels like `actor(alice)` which can be used to
write client mail rules to organize mail. By default, these stamps are sent
in an `X-Phabricator-Stamps` header.

If you use a client which can not use headers to route mail (like Gmail),
you can also include the stamps in the message body so mail rules based on
body content can route messages.
EOREMARKUP
        );
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_HEADER_STAMPS;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_HEADER_STAMPS => \Yii::t("app",'Mail Headers'),
            self::VALUE_BODY_STAMPS => \Yii::t("app",'Mail Headers and Body'),
        );
    }

}
