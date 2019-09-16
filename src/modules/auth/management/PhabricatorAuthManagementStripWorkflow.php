<?php

namespace orangins\modules\auth\management;

use orangins\modules\people\models\PhabricatorUser;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorAuthManagementStripWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementStripWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('strip')
            ->setExamples('**strip** [--user username] [--type type]')
            ->setSynopsis(\Yii::t("app", 'Remove multi-factor authentication from an account.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'user',
                        'param' => 'username',
                        'repeat' => true,
                        'help' => \Yii::t("app", 'Strip factors from specified users.'),
                    ),
                    array(
                        'name' => 'all-users',
                        'help' => \Yii::t("app", 'Strip factors from all users.'),
                    ),
                    array(
                        'name' => 'type',
                        'param' => 'factortype',
                        'repeat' => true,
                        'help' => \Yii::t("app", 'Strip a specific factor type.'),
                    ),
                    array(
                        'name' => 'all-types',
                        'help' => \Yii::t("app", 'Strip all factors, regardless of type.'),
                    ),
                    array(
                        'name' => 'force',
                        'help' => \Yii::t("app", 'Strip factors without prompting.'),
                    ),
                    array(
                        'name' => 'dry-run',
                        'help' => \Yii::t("app", 'Show factors, but do not strip them.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $usernames = $args->getArg('user');
        $all_users = $args->getArg('all-users');

        if ($usernames && $all_users) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify either specific users with %s, or all users with ' .
                    '%s, but not both.',
                    '--user',
                    '--all-users'));
        } else if (!$usernames && !$all_users) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Use %s to specify which user to strip factors from, or ' .
                    '%s to strip factors from all users.',
                    '--user',
                    '--all-users'));
        } else if ($usernames) {
            $users = PhabricatorUser::find()
                ->setViewer($this->getViewer())
                ->withUsernames($usernames)
                ->execute();

            $users_by_username = mpull($users, null, 'getUsername');
            foreach ($usernames as $username) {
                if (empty($users_by_username[$username])) {
                    throw new PhutilArgumentUsageException(
                        \Yii::t("app",
                            'No user exists with username "%s".',
                            $username));
                }
            }
        } else {
            $users = null;
        }

        $types = $args->getArg('type');
        $all_types = $args->getArg('all-types');
        if ($types && $all_types) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify either specific factors with --type, or all factors with ' .
                    '--all-types, but not both.'));
        } else if (!$types && !$all_types) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Use --type to specify which factor to strip, or --all-types to ' .
                    'strip all factors. Use `auth list-factors` to show the available ' .
                    'factor types.'));
        }

        if ($users && $types) {
            $factors = (new PhabricatorAuthFactorConfig())->loadAllWhere(
                'userPHID IN (%Ls) AND factorKey IN (%Ls)',
                mpull($users, 'getPHID'),
                $types);
        } else if ($users) {
            $factors = (new PhabricatorAuthFactorConfig())->loadAllWhere(
                'userPHID IN (%Ls)',
                mpull($users, 'getPHID'));
        } else if ($types) {
            $factors = (new PhabricatorAuthFactorConfig())->loadAllWhere(
                'factorKey IN (%Ls)',
                $types);
        } else {
            $factors = (new PhabricatorAuthFactorConfig())->loadAll();
        }

        if (!$factors) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'There are no matching factors to strip.'));
        }

        $handles = (new PhabricatorHandleQuery())
            ->setViewer($this->getViewer())
            ->withPHIDs(mpull($factors, 'getUserPHID'))
            ->execute();

        $console = PhutilConsole::getConsole();

        $console->writeOut("%s\n\n", \Yii::t("app", 'These auth factors will be stripped:'));

        foreach ($factors as $factor) {
            $impl = $factor->getImplementation();
            $console->writeOut(
                "    %s\t%s\t%s\n",
                $handles[$factor->getUserPHID()]->getName(),
                $factor->getFactorKey(),
                ($impl
                    ? $impl->getFactorName()
                    : '?'));
        }

        $is_dry_run = $args->getArg('dry-run');
        if ($is_dry_run) {
            $console->writeOut(
                "\n%s\n",
                \Yii::t("app", 'End of dry run.'));

            return 0;
        }

        $force = $args->getArg('force');
        if (!$force) {
            if (!$console->confirm(\Yii::t("app", 'Strip these authentication factors?'))) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app", 'User aborted the workflow.'));
            }
        }

        $console->writeOut("%s\n", \Yii::t("app", 'Stripping authentication factors...'));

        foreach ($factors as $factor) {
            $user = PhabricatorUser::find()
                ->setViewer($this->getViewer())
                ->withPHIDs(array($factor->getUserPHID()))
                ->executeOne();

            $factor->delete();

            if ($user) {
                $user->updateMultiFactorEnrollment();
            }
        }

        $console->writeOut("%s\n", \Yii::t("app", 'Done.'));

        return 0;
    }

}
