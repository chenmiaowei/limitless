<?php

namespace orangins\lib\infrastructure\management;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use PhutilArgumentUsageException;
use orangins\modules\people\models\PhabricatorUser;
use PhutilArgumentWorkflow;

/**
 * Class PhabricatorManagementWorkflow
 * @package orangins\lib\infrastructure\management
 * @author 陈妙威
 */
abstract class PhabricatorManagementWorkflow extends PhutilArgumentWorkflow
{

    /**
     * @throws \ReflectionException
     * @return string
     * @author 陈妙威
     */
    public function getClassShortName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isExecutable()
    {
        return true;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getViewer()
    {
        // Some day, we might provide a more general viewer mechanism to scripts.
        // For now, workflows can call this method for convenience and future
        // flexibility.
        return PhabricatorUser::getOmnipotentUser();
    }

    /**
     * @param $time
     * @return false|int|null
     * @author 陈妙威
     * @throws PhutilArgumentUsageException
     */
    protected function parseTimeArgument($time)
    {
        if (!strlen($time)) {
            return null;
        }

        $epoch = strtotime($time);
        if ($epoch <= 0) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'Unable to parse time "{0}".', [
                    $time
                ]));
        }
        return $epoch;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function newContentSource()
    {
        return PhabricatorContentSource::newForSource(
            PhabricatorConsoleContentSource::SOURCECONST);
    }

    /**
     * @param $label
     * @param $message
     * @author 陈妙威
     */
    protected function logInfo($label, $message)
    {
        $this->logRaw(
            tsprintf(
                "**<bg:blue> %s </bg>** %s\n",
                $label,
                $message));
    }

    /**
     * @param $label
     * @param $message
     * @author 陈妙威
     */
    protected function logOkay($label, $message)
    {
        $this->logRaw(
            tsprintf(
                "**<bg:green> %s </bg>** %s\n",
                $label,
                $message));
    }

    /**
     * @param $label
     * @param $message
     * @author 陈妙威
     */
    protected function logWarn($label, $message)
    {
        $this->logRaw(
            tsprintf(
                "**<bg:yellow> %s </bg>** %s\n",
                $label,
                $message));
    }

    /**
     * @param $label
     * @param $message
     * @author 陈妙威
     */
    protected function logFail($label, $message)
    {
        $this->logRaw(
            tsprintf(
                "**<bg:red> %s </bg>** %s\n",
                $label,
                $message));
    }

    /**
     * @param $message
     * @author 陈妙威
     */
    private function logRaw($message)
    {
        fprintf(STDERR, '%s', $message);
    }

}
