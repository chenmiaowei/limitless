<?php

namespace orangins\modules\config\management;

use orangins\lib\env\PhabricatorConfigLocalSource;
use orangins\modules\config\exception\PhabricatorConfigValidationException;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorConfigManagementSetWorkflow
 * @package orangins\modules\config\management
 * @author 陈妙威
 */
final class PhabricatorConfigManagementSetWorkflow
    extends PhabricatorConfigManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('set')
            ->setExamples(
                "**set** __key__ __value__\n" .
                "**set** __key__ --stdin < value.json")
            ->setSynopsis(\Yii::t("app",'Set a local configuration value.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'database',
                        'help' => \Yii::t("app",
                            'Update configuration in the database instead of ' .
                            'in local configuration.'),
                    ),
                    array(
                        'name' => 'stdin',
                        'help' => \Yii::t("app",'Read option value from stdin.'),
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
     * @throws \AphrontQueryException
     * @throws \PhutilProxyException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * @throws \PhutilArgumentSpecificationException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $argv = $args->getArg('args');
        if (count($argv) == 0) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'Specify a configuration key and a value to set it to.'));
        }

        $is_stdin = $args->getArg('stdin');

        $key = $argv[0];

        if ($is_stdin) {
            if (count($argv) > 1) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Too many arguments: expected only a key when using "--stdin".'));
            }

            fprintf(STDERR, tsprintf("%s\n", \Yii::t("app",'Reading value from stdin...')));
            $value = file_get_contents('php://stdin');
        } else {
            if (count($argv) == 1) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        "Specify a value to set the key '%s' to.",
                        $key));
            }

            if (count($argv) > 2) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Too many arguments: expected one key and one value.'));
            }

            $value = $argv[1];
        }


        $options = PhabricatorApplicationConfigOptions::loadAllOptions();
        if (empty($options[$key])) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    "No such configuration key '%s'! Use `%s` to list all keys.",
                    $key,
                    'config list'));
        }

        $option = $options[$key];

        $type = $option->newOptionType();
        if ($type) {
            try {
                $value = $type->newValueFromCommandLineValue(
                    $option,
                    $value);
                $type->validateStoredValue($option, $value);
            } catch (PhabricatorConfigValidationException $ex) {
                throw new PhutilArgumentUsageException($ex->getMessage());
            }
        } else {
            // NOTE: For now, this handles both "wild" values and custom types.
            $type = $option->getType();
            switch ($type) {
                default:
                    $value = json_decode($value, true);
                    if (!is_array($value)) {
                        switch ($type) {
                            default:
                                $message = \Yii::t("app",
                                    'Config key "%s" is of type "%s". Specify it in JSON.',
                                    $key,
                                    $type);
                                break;
                        }
                        throw new PhutilArgumentUsageException($message);
                    }
                    break;
            }
        }

        $use_database = $args->getArg('database');
        if ($option->getLocked() && $use_database) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Config key "%s" is locked and can only be set in local ' .
                    'configuration. To learn more, see "%s" in the documentation.',
                    $key,
                    \Yii::t("app",'Configuration Guide: Locked and Hidden Configuration')));
        }

        try {
            $option->getGroup()->validateOption($option, $value);
        } catch (PhabricatorConfigValidationException $validation) {
            // Convert this into a usage exception so we don't dump a stack trace.
            throw new PhutilArgumentUsageException($validation->getMessage());
        }

        if ($use_database) {
            $config_type = 'database';
            $config_entry = PhabricatorConfigEntry::loadConfigEntry($key);
            $config_entry->setValue($value);

            // If the entry has been deleted, resurrect it.
            $config_entry->setIsDeleted(0);

            $config_entry->save();
        } else {
            $config_type = 'local';
            (new PhabricatorConfigLocalSource())
                ->setKeys(array($key => $value));
        }

        $console->writeOut(
            "%s\n",
            \Yii::t("app","Set '{0}' in {1} configuration.", [$key, $config_type]));
    }

}
