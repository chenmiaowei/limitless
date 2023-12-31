<?php

namespace orangins\modules\settings\setting;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\settings\panel\PhabricatorDisplayPreferencesSettingsPanel;
use Exception;

/**
 * Class PhabricatorEditorSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEditorSetting
    extends PhabricatorStringSetting
{

    /**
     *
     */
    const SETTINGKEY = 'editor';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Editor Link');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorDisplayPreferencesSettingsPanel::PANELKEY;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getSettingOrder()
    {
        return 300;
    }

    /**
     * @return null|string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            "Many text editors can be configured as URI handlers for special " .
            "protocols like `editor://`. If you have such an editor, Phabricator " .
            "can generate links that you can click to open files locally." .
            "\n\n" .
            "These special variables are supported:" .
            "\n\n" .
            "| Value | Replaced With |\n" .
            "|-------|---------------|\n" .
            "| `%%f`  | Filename |\n" .
            "| `%%l`  | Line Number |\n" .
            "| `%%r`  | Repository Callsign |\n" .
            "| `%%%%`  | Literal `%%` |\n" .
            "\n\n" .
            "For complete instructions on editor configuration, " .
            "see **[[ {0} | {1} ]]**.",
           [
               PhabricatorEnv::getDoclink('User Guide: Configuring an External Editor'),
               \Yii::t("app", 'User Guide: Configuring an External Editor')
           ]);
    }

    /**
     * @param $value
     * @throws Exception
     * @author 陈妙威
     */
    public function validateTransactionValue($value)
    {
        if (!strlen($value)) {
            return;
        }

        $ok = PhabricatorHelpEditorProtocolController::hasAllowedProtocol($value);
        if ($ok) {
            return;
        }

        $allowed_key = 'uri.allowed-editor-protocols';
        $allowed_protocols = PhabricatorEnv::getEnvConfig($allowed_key);

        $proto_names = array();
        foreach (array_keys($allowed_protocols) as $protocol) {
            $proto_names[] = $protocol . '://';
        }

        throw new Exception(
            \Yii::t("app",
                'Editor link has an invalid or missing protocol. You must ' .
                'use a whitelisted editor protocol from this list: %s. To ' .
                'add protocols, update "%s" in Config.',
                implode(', ', $proto_names),
                $allowed_key));
    }

}
