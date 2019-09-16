<?php

namespace orangins\modules\config\option;

/**
 * Class PhabricatorPHPMailerConfigOptions
 * @package orangins\modules\config\option
 * @author 陈妙威
 */
final class PhabricatorPHPMailerConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'PHPMailer');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app", 'Configure PHPMailer.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-mailer';
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
            $this->newOption('phpmailer.mailer', 'string', 'smtp')
                ->setLocked(true)
                ->setSummary(\Yii::t("app", 'Configure mailer used by PHPMailer.'))
                ->setDescription(
                    \Yii::t("app",
                        "If you're using PHPMailer to send email, provide the mailer and " .
                        "options here. PHPMailer is much more enormous than " .
                        "PHPMailerLite, and provides more mailers and greater enormity. " .
                        "You need it when you want to use SMTP instead of sendmail as the " .
                        "mailer.")),
            $this->newOption('phpmailer.smtp-host', 'string', null)
                ->setLocked(true)
                ->setDescription(\Yii::t("app", 'Host for SMTP.')),
            $this->newOption('phpmailer.smtp-port', 'int', 25)
                ->setLocked(true)
                ->setDescription(\Yii::t("app", 'Port for SMTP.')),
            // TODO: Implement "enum"? Valid values are empty, 'tls', or 'ssl'.
            $this->newOption('phpmailer.smtp-protocol', 'string', null)
                ->setLocked(true)
                ->setSummary(\Yii::t("app", 'Configure TLS or SSL for SMTP.'))
                ->setDescription(
                    \Yii::t("app",
                        "Using PHPMailer with SMTP, you can set this to one of '{0}' or " .
                        "'{1}' to use TLS or SSL, respectively. Leave it blank for " .
                        "vanilla SMTP. If you're sending via Gmail, set it to '{2}'.",[
                            'tls',
                            'ssl',
                            'ssl'
                        ])),
            $this->newOption('phpmailer.smtp-user', 'string', null)
                ->setLocked(true)
                ->setDescription(\Yii::t("app", 'Username for SMTP.')),
            $this->newOption('phpmailer.smtp-password', 'string', null)
                ->setHidden(true)
                ->setDescription(\Yii::t("app", 'Password for SMTP.')),
            $this->newOption('phpmailer.smtp-encoding', 'string', 'base64')
                ->setSummary(\Yii::t("app", 'Configure how mail is encoded.'))
                ->setDescription(
                    \Yii::t("app",
                        "Mail is normally encoded in `8bit`, which works correctly with " .
                        "most MTAs. However, some MTAs do not work well with this " .
                        "encoding. If you're having trouble with mail being mangled or " .
                        "arriving with too many or too few newlines, you may try " .
                        "adjusting this setting.\n\n" .
                        "Supported values are `8bit`, `quoted-printable`, " .
                        "`7bit`, `binary` and `base64`.")),
        );
    }

}
