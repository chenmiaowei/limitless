<?php

namespace orangins\modules\guides\guidance;

use orangins\lib\OranginsObject;
use PhutilSafeHTML;
use PhutilSortVector;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorGuidanceMessage
 * @package orangins\modules\guides\guidance
 * @author 陈妙威
 */
final class PhabricatorGuidanceMessage
    extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $message;
    /**
     * @var string
     */
    private $severity = self::SEVERITY_NOTICE;
    /**
     * @var int
     */
    private $priority = 1000;

    /**
     *
     */
    const SEVERITY_NOTICE = 'notice';
    /**
     *
     */
    const SEVERITY_WARNING = 'warning';

    /**
     * @param $severity
     * @return $this
     * @author 陈妙威
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $message
     * @return $this
     * @author 陈妙威
     */
    public function setMessage($message)
    {
        $this->message = new PhutilSafeHTML($message);
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSortVector()
    {
        return (new PhutilSortVector())
            ->addInt($this->getPriority());
    }

    /**
     * @param $priority
     * @return $this
     * @author 陈妙威
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSeverityStrength()
    {
        $map = array(
            self::SEVERITY_NOTICE => 1,
            self::SEVERITY_WARNING => 2,
        );

        return ArrayHelper::getValue($map, $this->getSeverity(), 0);
    }
}
