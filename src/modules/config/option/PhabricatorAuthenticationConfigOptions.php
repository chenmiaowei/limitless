<?php

namespace orangins\modules\config\option;

/**
 * Class PhabricatorAuthenticationConfigOptions
 * @package orangins\modules\config\option
 * @author 陈妙威
 */
final class PhabricatorAuthenticationConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Authentication');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app", 'Options relating to authentication.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'icon-key';
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
            $this->newOption('auth.require-email-verification', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t("app", 'Require email verification'),
                        \Yii::t("app", "Don't require email verification"),
                    ))
                ->setSummary(
                    \Yii::t("app", 'Require email verification before a user can log in.'))
                ->setDescription(
                    \Yii::t("app",
                        'If true, email addresses must be verified (by clicking a link ' .
                        'in an email) before a user can login. By default, verification ' .
                        'is optional unless {{auth.email-domains}} is nonempty.')),
            $this->newOption('auth.require-approval', 'bool', true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app", 'Require Administrators to Approve Accounts'),
                        \Yii::t("app", "Don't Require Manual Approval"),
                    ))
                ->setSummary(
                    \Yii::t("app", 'Require administrators to approve new accounts.'))
                ->setDescription(
                    \Yii::t("app",
                        "Newly registered Phabricator accounts can either be placed " .
                        "into a manual approval queue for administrative review, or " .
                        "automatically activated immediately. The approval queue is " .
                        "enabled by default because it gives you greater control over " .
                        "who can register an account and access Phabricator.\n\n" .
                        "If your install is completely public, or on a VPN, or users can " .
                        "only register with a trusted provider like LDAP, or you've " .
                        "otherwise configured Phabricator to prevent unauthorized " .
                        "registration, you can disable the queue to reduce administrative " .
                        "overhead.\n\n" .
                        "NOTE: Before you disable the queue, make sure " .
                        "{{auth.email-domains}} is configured correctly " .
                        "for your install!")),
            $this->newOption('auth.email-domains', 'list<string>', array())
                ->setSummary(\Yii::t("app", 'Only allow registration from particular domains.'))
                ->setDescription(
                    \Yii::t("app",
                        "You can restrict allowed email addresses to certain domains " .
                        "(like `yourcompany.com`) by setting a list of allowed domains " .
                        "here.\n\nUsers will only be allowed to register using email " .
                        "addresses at one of the domains, and will only be able to add " .
                        "new email addresses for these domains. If you configure this, " .
                        "it implies {{auth.require-email-verification}}.\n\n" .
                        "You should omit the `@` from domains. Note that the domain must " .
                        "match exactly. If you allow `yourcompany.com`, that permits " .
                        "`joe@yourcompany.com` but rejects `joe@mail.yourcompany.com`."))
                ->addExample(
                    "yourcompany.com\nmail.yourcompany.com",
                    \Yii::t("app", 'Valid Setting')),
            $this->newOption('account.editable', 'bool', true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app", 'Allow editing'),
                        \Yii::t("app", 'Prevent editing'),
                    ))
                ->setSummary(
                    \Yii::t("app",
                        'Determines whether or not basic account information is editable.'))
                ->setDescription(
                    \Yii::t("app",
                        'This option controls whether users can edit account email ' .
                        'addresses and profile real names.' .
                        "\n\n" .
                        'If you set up Phabricator to automatically synchronize account ' .
                        'information from some other authoritative system, you can ' .
                        'prevent users from making these edits to ensure information ' .
                        'remains consistent across both systems.')),
            $this->newOption('account.minimum-password-length', 'int', 9)
                ->setSummary(\Yii::t("app", 'Minimum password length.'))
                ->setDescription(
                    \Yii::t("app",
                        'When users set or reset a password, it must have at least this ' .
                        'many characters.')),
        );
    }

}
