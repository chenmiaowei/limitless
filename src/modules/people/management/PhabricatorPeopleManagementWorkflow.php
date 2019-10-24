<?php

namespace orangins\modules\people\management;

use orangins\lib\infrastructure\management\PhabricatorManagementWorkflow;
use orangins\lib\infrastructure\storage\lisk\LiskMigrationIterator;
use orangins\modules\people\models\PhabricatorUser;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorPeopleManagementWorkflow
 * @package orangins\modules\people\management
 * @author 陈妙威
 */
abstract class PhabricatorPeopleManagementWorkflow
    extends PhabricatorManagementWorkflow
{

    /**
     * @param PhutilArgumentParser $args
     * @return array|LiskMigrationIterator|null
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildIterator(PhutilArgumentParser $args)
    {
        $usernames = $args->getArg('users');

        if ($args->getArg('all')) {
            if ($usernames) {
                throw new PhutilArgumentUsageException(
                    pht(
                        'Specify either a list of users or `%s`, but not both.',
                        '--all'));
            }
            return new LiskMigrationIterator(new PhabricatorUser());
        }

        if ($usernames) {
            return $this->loadUsersWithUsernames($usernames);
        }

        return null;
    }

    /**
     * @param array $usernames
     * @return array
     * @throws PhutilArgumentUsageException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function loadUsersWithUsernames(array $usernames)
    {
        $users = array();
        foreach ($usernames as $username) {
            $query = PhabricatorUser::find()
                ->setViewer($this->getViewer())
                ->withUsernames(array($username))
                ->executeOne();

            if (!$query) {
                throw new PhutilArgumentUsageException(
                    pht(
                        '"%s" is not a valid username.',
                        $username));
            }
            $users[] = $query;
        }

        return $users;
    }
}
