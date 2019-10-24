<?php

namespace orangins\modules\people\management;

use orangins\modules\file\PhabricatorFilesComposeAvatarBuiltinFile;
use orangins\modules\people\models\PhabricatorUser;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorPeopleProfileImageWorkflow
 * @package orangins\modules\people\management
 * @author 陈妙威
 */
final class PhabricatorPeopleProfileImageWorkflow
    extends PhabricatorPeopleManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('profileimage')
            ->setExamples('**profileimage** --users __username__')
            ->setSynopsis(pht('Generate default profile images.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'all',
                        'help' => pht(
                            'Generate default profile images for all users.'),
                    ),
                    array(
                        'name' => 'force',
                        'short' => 'f',
                        'help' => pht(
                            'Force a default profile image to be replaced.'),
                    ),
                    array(
                        'name' => 'users',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws PhutilArgumentUsageException
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $is_force = $args->getArg('force');
        $is_all = $args->getArg('all');

        $gd = function_exists('imagecreatefromstring');
        if (!$gd) {
            throw new PhutilArgumentUsageException(
                pht(
                    'GD is not installed for php-cli. Aborting.'));
        }

        /** @var PhabricatorUser[] $iterator */
        $iterator = $this->buildIterator($args);
        if (!$iterator) {
            throw new PhutilArgumentUsageException(
                pht(
                    'Either specify a list of users to update, or use `%s` ' .
                    'to update all users.',
                    '--all'));
        }

        $version = PhabricatorFilesComposeAvatarBuiltinFile::VERSION;
        $generator = new PhabricatorFilesComposeAvatarBuiltinFile();

        foreach ($iterator as $user) {
            $username = $user->getUsername();
            $default_phid = $user->getDefaultProfileImagePHID();
            $gen_version = $user->getDefaultProfileImageVersion();

            $generate = false;
            if ($gen_version != $version) {
                $generate = true;
            }

            if ($default_phid == null || $is_force || $generate) {
                $console->writeOut(
                    "%s\n",
                    pht(
                        'Generating profile image for "%s".',
                        $username));

                $generator->updateUser($user);
            } else {
                $console->writeOut(
                    "%s\n",
                    pht(
                        'Default profile image "%s" already set for "%s".',
                        $version,
                        $username));
            }
        }
    }

}
