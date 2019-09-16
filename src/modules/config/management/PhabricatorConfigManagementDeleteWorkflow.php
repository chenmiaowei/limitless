<?php

namespace orangins\modules\config\management;

use orangins\lib\env\PhabricatorConfigDatabaseSource;
use orangins\lib\env\PhabricatorConfigLocalSource;
use orangins\modules\config\models\PhabricatorConfigEntry;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorConfigManagementDeleteWorkflow
 * @package orangins\modules\config\management
 * @author 陈妙威
 */
final class PhabricatorConfigManagementDeleteWorkflow
    extends PhabricatorConfigManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('delete')
            ->setExamples('**delete** __key__')
            ->setSynopsis(\Yii::t("app",'Delete a local configuration value.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'database',
                        'help' => \Yii::t("app",
                            'Delete configuration in the database instead of ' .
                            'in local configuration.'),
                    ),
                    array(
                        'name' => 'args',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilProxyException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \AphrontQueryException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $argv = $args->getArg('args');
        if (count($argv) == 0) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'Specify a configuration key to delete.'));
        }

        $key = $argv[0];

        if (count($argv) > 1) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'Too many arguments: expected one key.'));
        }


        $use_database = $args->getArg('database');
        if ($use_database) {
            $config = new PhabricatorConfigDatabaseSource('default');
            $config_type = 'database';
        } else {
            $config = new PhabricatorConfigLocalSource();
            $config_type = 'local';
        }
        $values = $config->getKeys(array($key));
        if (!$values) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    "Configuration key '%s' is not set in %s configuration!",
                    $key,
                    $config_type));
        }

        if ($use_database) {
            $config_entry = PhabricatorConfigEntry::loadConfigEntry($key);
            $config_entry->setIsDeleted(1);
            $config_entry->save();
        } else {
            $config->deleteKeys(array($key));
        }

        $console->writeOut(
            "%s\n",
            \Yii::t("app","Deleted '%s' from %s configuration.", $key, $config_type));
    }

}
