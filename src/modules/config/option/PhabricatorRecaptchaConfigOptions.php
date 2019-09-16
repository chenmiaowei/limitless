<?php

namespace orangins\modules\config\option;

/**
 * Class PhabricatorRecaptchaConfigOptions
 * @package orangins\modules\config\option
 * @author 陈妙威
 */
final class PhabricatorRecaptchaConfigOptions extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Integration with Recaptcha');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app",'Configure Recaptcha captchas.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-recycle';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroup()
    {
        return 'core';
    }

    /**
     * @return array|PhabricatorConfigOption[]
     * @author 陈妙威
     */
    public function getOptions()
    {

        return array(
            $this->newOption('recaptcha.enabled', 'bool', false)
                ->setLocked(true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Enable Recaptcha'),
                        \Yii::t("app",'Disable Recaptcha'),
                    ))
                ->setSummary(\Yii::t("app",'Enable captchas with Recaptcha.'))
                ->setDescription(
                    \Yii::t("app",
                        'Enable recaptcha to require users solve captchas after a few ' .
                        'failed login attempts. This hinders brute-force attacks against ' .
                        'user passwords. For more information, see http://recaptcha.net/')),
            $this->newOption('recaptcha.public-key', 'string', null)
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app",'Recaptcha public key, obtained by signing up for Recaptcha.')),
            $this->newOption('recaptcha.private-key', 'string', null)
                ->setHidden(true)
                ->setDescription(
                    \Yii::t("app",'Recaptcha private key, obtained by signing up for Recaptcha.')),
        );
    }

}
