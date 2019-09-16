<?php

namespace orangins\modules\auth\management;

use orangins\modules\auth\provider\PhabricatorLDAPAuthProvider;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;
use PhutilOpaqueEnvelope;

/**
 * Class PhabricatorAuthManagementLDAPWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementLDAPWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('ldap')
            ->setExamples('**ldap**')
            ->setSynopsis(
                \Yii::t("app", 'Analyze and diagnose issues with LDAP configuration.'));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilConsoleStdinNotInteractiveException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $console->getServer()->setEnableLog(true);

        PhabricatorLDAPAuthProvider::assertLDAPExtensionInstalled();

        $provider = PhabricatorLDAPAuthProvider::getLDAPProvider();
        if (!$provider) {
            $console->writeOut(
                "%s\n",
                \Yii::t("app", 'The LDAP authentication provider is not enabled.'));
            exit(1);
        }

        if (!function_exists('ldap_connect')) {
            $console->writeOut(
                "%s\n",
                \Yii::t("app", 'The LDAP extension is not enabled.'));
            exit(1);
        }

        $adapter = $provider->getAdapter();

        $console->writeOut("%s\n", \Yii::t("app", 'Enter LDAP Credentials'));
        $username = phutil_console_prompt(\Yii::t("app", 'LDAP Username: '));
        if (!strlen($username)) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'You must enter an LDAP username.'));
        }

        phutil_passthru('stty -echo');
        $password = phutil_console_prompt(\Yii::t("app", 'LDAP Password: '));
        phutil_passthru('stty echo');

        if (!strlen($password)) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'You must enter an LDAP password.'));
        }

        $adapter->setLoginUsername($username);
        $adapter->setLoginPassword(new PhutilOpaqueEnvelope($password));

        $console->writeOut("\n");
        $console->writeOut("%s\n", \Yii::t("app", 'Connecting to LDAP...'));

        $account_id = $adapter->getAccountID();
        if ($account_id) {
            $console->writeOut("%s\n", \Yii::t("app", 'Found LDAP Account: %s', $account_id));
        } else {
            $console->writeOut("%s\n", \Yii::t("app", 'Unable to find LDAP account!'));
        }

        return 0;
    }

}
