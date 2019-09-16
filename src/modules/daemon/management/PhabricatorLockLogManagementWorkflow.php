<?php

namespace orangins\modules\daemon\management;

use orangins\lib\env\PhabricatorConfigLocalSource;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\modules\daemon\garbagecollector\PhabricatorDaemonLockLogGarbageCollector;
use orangins\modules\daemon\models\PhabricatorDaemonLockLog;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsoleTable;

/**
 * Class PhabricatorLockLogManagementWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorLockLogManagementWorkflow
    extends PhabricatorLockManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('log')
            ->setSynopsis(\Yii::t("app", 'Enable, disable, or show the lock log.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'enable',
                        'help' => \Yii::t("app", 'Enable the lock log.'),
                    ),
                    array(
                        'name' => 'disable',
                        'help' => \Yii::t("app", 'Disable the lock log.'),
                    ),
                    array(
                        'name' => 'name',
                        'param' => 'name',
                        'help' => \Yii::t("app", 'Review logs for a specific lock.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilProxyException
     * @throws \ReflectionException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $is_enable = $args->getArg('enable');
        $is_disable = $args->getArg('disable');

        if ($is_enable && $is_disable) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'You can not both "--enable" and "--disable" the lock log.'));
        }

        $with_name = $args->getArg('name');

        if ($is_enable || $is_disable) {
            if (strlen($with_name)) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'You can not both "--enable" or "--disable" with search ' .
                        'parameters like "--name".'));
            }

            $gc = new PhabricatorDaemonLockLogGarbageCollector();
            $is_enabled = (bool)$gc->getRetentionPolicy();

            $config_key = 'phd.garbage-collection';
            $const = $gc->getCollectorConstant();
            $value = PhabricatorEnv::getEnvConfig($config_key);

            if ($is_disable) {
                if (!$is_enabled) {
                    echo tsprintf(
                        "%s\n",
                        \Yii::t("app", 'Lock log is already disabled.'));
                    return 0;
                }
                echo tsprintf(
                    "%s\n",
                    \Yii::t("app", 'Disabling the lock log.'));

                unset($value[$const]);
            } else {
                if ($is_enabled) {
                    echo tsprintf(
                        "%s\n",
                        \Yii::t("app", 'Lock log is already enabled.'));
                    return 0;
                }
                echo tsprintf(
                    "%s\n",
                    \Yii::t("app", 'Enabling the lock log.'));

                $value[$const] = phutil_units('24 hours in seconds');
            }

            (new PhabricatorConfigLocalSource())
                ->setKeys(
                    array(
                        $config_key => $value,
                    ));

            echo tsprintf(
                "%s\n",
                \Yii::t("app", 'Done.'));

            echo tsprintf(
                "%s\n",
                \Yii::t("app", 'Restart daemons to apply changes.'));

            return 0;
        }

        $table = new PhabricatorDaemonLockLog();
        $conn = $table->establishConnection('r');

        $parts = array();
        if (strlen($with_name)) {
            $parts[] = qsprintf(
                $conn,
                'lockName = %s',
                $with_name);
        }

        if (!$parts) {
            $constraint = qsprintf($conn, '1 = 1');
        } else {
            $constraint = qsprintf($conn, '%LA', $parts);
        }

        $logs = $table->loadAllWhere(
            '%Q ORDER BY id DESC LIMIT 100',
            $constraint);
        $logs = array_reverse($logs);

        if (!$logs) {
            echo tsprintf(
                "%s\n",
                \Yii::t("app", 'No matching lock logs.'));
            return 0;
        }

        $table = (new PhutilConsoleTable())
            ->setBorders(true)
            ->addColumn(
                'id',
                array(
                    'title' => \Yii::t("app", 'Lock'),
                ))
            ->addColumn(
                'name',
                array(
                    'title' => \Yii::t("app", 'Name'),
                ))
            ->addColumn(
                'acquired',
                array(
                    'title' => \Yii::t("app", 'Acquired'),
                ))
            ->addColumn(
                'released',
                array(
                    'title' => \Yii::t("app", 'Released'),
                ))
            ->addColumn(
                'held',
                array(
                    'title' => \Yii::t("app", 'Held'),
                ))
            ->addColumn(
                'parameters',
                array(
                    'title' => \Yii::t("app", 'Parameters'),
                ))
            ->addColumn(
                'context',
                array(
                    'title' => \Yii::t("app", 'Context'),
                ));

        $viewer = $this->getViewer();

        foreach ($logs as $log) {
            $created = $log->created_at;
            $released = $log->getLockReleased();

            if ($released) {
                $held = '+' . ($released - $created);
            } else {
                $held = null;
            }

            $created = OranginsViewUtil::phabricator_datetime($created, $viewer);
            $released = OranginsViewUtil::phabricator_datetime($released, $viewer);

            $parameters = $log->getLockParameters();
            $context = $log->getLockContext();

            $table->addRow(
                array(
                    'id' => $log->getID(),
                    'name' => $log->getLockName(),
                    'acquired' => $created,
                    'released' => $released,
                    'held' => $held,
                    'parameters' => $this->flattenParameters($parameters),
                    'context' => $this->flattenParameters($context),
                ));
        }

        $table->draw();

        return 0;
    }

    /**
     * @param array $params
     * @param bool $keys
     * @return array|string
     * @author 陈妙威
     */
    private function flattenParameters(array $params, $keys = true)
    {
        $flat = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = $this->flattenParameters($value, false);
            }
            if ($keys) {
                $flat[] = "{$key}={$value}";
            } else {
                $flat[] = "{$value}";
            }
        }

        if ($keys) {
            $flat = implode(', ', $flat);
        } else {
            $flat = implode(' ', $flat);
        }

        return $flat;
    }

}
