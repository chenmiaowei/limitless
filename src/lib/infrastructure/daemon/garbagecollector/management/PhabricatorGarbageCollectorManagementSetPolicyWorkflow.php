<?php

namespace orangins\lib\infrastructure\daemon\garbagecollector\management;

use orangins\lib\env\PhabricatorConfigLocalSource;
use orangins\lib\env\PhabricatorEnv;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorGarbageCollectorManagementSetPolicyWorkflow
 * @package orangins\lib\infrastructure\daemon\garbagecollector\management
 * @author 陈妙威
 */
final class PhabricatorGarbageCollectorManagementSetPolicyWorkflow
    extends PhabricatorGarbageCollectorManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('set-policy')
            ->setExamples(
                "**set-policy** --collector __collector__ --days 30\n" .
                "**set-policy** --collector __collector__ --indefinite\n" .
                "**set-policy** --collector __collector__ --default")
            ->setSynopsis(
                \Yii::t("app",
                    'Change retention policies for a garbage collector.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'collector',
                        'param' => 'const',
                        'help' => \Yii::t("app",
                            'Constant identifying the garbage collector.'),
                    ),
                    array(
                        'name' => 'indefinite',
                        'help' => \Yii::t("app",
                            'Set an indefinite retention policy.'),
                    ),
                    array(
                        'name' => 'default',
                        'help' => \Yii::t("app",
                            'Use the default retention policy.'),
                    ),
                    array(
                        'name' => 'days',
                        'param' => 'count',
                        'help' => \Yii::t("app",
                            'Retain data for the specified number of days.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilProxyException
     * @throws \ReflectionException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $config_key = 'phd.garbage-collection';

        $collector = $this->getCollector($args->getArg('collector'));

        $days = $args->getArg('days');
        $indefinite = $args->getArg('indefinite');
        $default = $args->getArg('default');

        $count = 0;
        if ($days !== null) {
            $count++;
        }
        if ($indefinite) {
            $count++;
        }
        if ($default) {
            $count++;
        }

        if (!$count) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Choose a policy with "{0}", "{1}" or "{2}".',
                    [
                        '--days',
                        '--indefinite',
                        '--default'
                    ]));
        }

        if ($count > 1) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Options "{0}", "{1}" and "{2}" represent mutually exclusive ways ' .
                    'to choose a policy. Specify only one.',
                    [
                        '--days',
                        '--indefinite',
                        '--default'
                    ]));
        }

        if ($days !== null) {
            $days = (int)$days;
            if ($days < 1) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Specify a positive number of days to retain data for.'));
            }
        }

        $collector_const = $collector->getCollectorConstant();
        $value = PhabricatorEnv::getEnvConfig($config_key);

        if ($days !== null) {
            echo tsprintf(
                "%s\n",
                \Yii::t("app",
                    'Setting retention policy for "{0}" to {1} day(s).',
                    [
                        $collector->getCollectorName(),
                        $days
                    ]));

            $value[$collector_const] = phutil_units($days . ' days in seconds');
        } else if ($indefinite) {
            echo tsprintf(
                "%s\n",
                \Yii::t("app",
                    'Setting "{0}" to be retained indefinitely.',
                    [
                        $collector->getCollectorName()
                    ]));

            $value[$collector_const] = null;
        } else {
            echo tsprintf(
                "%s\n",
                \Yii::t("app",
                    'Restoring "{0}" to the default retention policy.',
                    [
                        $collector->getCollectorName()
                    ]));

            unset($value[$collector_const]);
        }

        (new PhabricatorConfigLocalSource())
            ->setKeys(
                array(
                    $config_key => $value,
                ));

        echo tsprintf(
            "%s\n",
            \Yii::t("app",
                'Wrote new policy to local configuration.'));

        echo tsprintf(
            "%s\n",
            \Yii::t("app",
                'This change will take effect the next time the daemons are ' .
                'restarted.'));

        return 0;
    }

}
