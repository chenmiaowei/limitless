<?php

namespace orangins\modules\auth\management;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use orangins\modules\people\models\PhabricatorUser;
use PhutilConsole;

/**
 * Class PhabricatorAuthManagementRecoverWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementRecoverWorkflow extends PhabricatorAuthManagementWorkflow
{

    /**
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('recover')
            ->setExamples('**recover** __username__')
            ->setSynopsis(
                \Yii::t("app",
                    'Recover access to an account if you have locked yourself out ' .
                    'of Phabricator.'))
            ->setArguments(
                array(
                    'username' => array(
                        'name' => 'username',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws PhutilArgumentUsageException
     * @throws \AphrontQueryException
     * @throws \PhutilArgumentSpecificationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $usernames = $args->getArg('username');
        if (!$usernames) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'You must specify the username of the account to recover.'));
        } else if (count($usernames) > 1) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'You can only recover the username for one account.'));
        }
        $username = OranginsUtil::head($usernames);

        $user = PhabricatorUser::find()
            ->setViewer($this->getViewer())
            ->withUsernames(array($username))
            ->executeOne();

        if (!$user) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'No such user "%s" to recover.',
                    $username));
        }

        if (!$user->canEstablishWebSessions()) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'This account ("%s") can not establish web sessions, so it is ' .
                    'not possible to generate a functional recovery link. Special ' .
                    'accounts like daemons and mailing lists can not log in via the ' .
                    'web UI.',
                    $username));
        }

        $engine = new PhabricatorAuthSessionEngine();
        $onetime_uri = $engine->getOneTimeLoginURI(
            $user,
            null,
            PhabricatorAuthSessionEngine::ONETIME_RECOVER);

        $console = PhutilConsole::getConsole();
        $console->writeOut(
            \Yii::t("app",
                'Use this link to recover access to the "{0}" account from the web ' .
                'interface:', [
                    $username
                ]));
        $console->writeOut("\n\n");
        $console->writeOut('    %s', $onetime_uri);
        $console->writeOut("\n\n");
        $console->writeOut(
            "%s\n",
            \Yii::t("app",
                'After logging in, you can use the "Auth" application to add or ' .
                'restore authentication providers and allow normal logins to ' .
                'succeed.'));

        return 0;
    }

}
