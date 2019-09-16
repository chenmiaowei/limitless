<?php

namespace orangins\modules\auth\management;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\system\engine\PhabricatorSystemActionEngine;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilNumber;

/**
 * Class PhabricatorAuthManagementUnlimitWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementUnlimitWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('unlimit')
            ->setExamples('**unlimit** --user __username__ --all')
            ->setSynopsis(
                \Yii::t("app",
                    'Reset action counters so a user can continue taking ' .
                    'rate-limited actions.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'user',
                        'param' => 'username',
                        'help' => \Yii::t("app", 'Reset action counters for this user.'),
                    ),
                    array(
                        'name' => 'all',
                        'help' => \Yii::t("app", 'Reset all counters.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $username = $args->getArg('user');
        if (!strlen($username)) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Use %s to choose a user to reset actions for.', '--user'));
        }

        $user = PhabricatorUser::find()
            ->setViewer($this->getViewer())
            ->withUsernames(array($username))
            ->executeOne();
        if (!$user) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'No user exists with username "%s".',
                    $username));
        }

        $all = $args->getArg('all');
        if (!$all) {
            // TODO: Eventually, let users reset specific actions. For now, we
            // require `--all` so that usage won't change when you can reset in a
            // more tailored way.
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify %s to reset all action counters.', '--all'));
        }

        $count = PhabricatorSystemActionEngine::resetActions(
            array(
                $user->getPHID(),
            ));

        echo \Yii::t("app", 'Reset %s action(s).', new PhutilNumber($count)) . "\n";

        return 0;
    }

}
