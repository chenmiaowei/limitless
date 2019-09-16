<?php

namespace orangins\modules\system\systemaction;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorSystemAction
 * @package orangins\modules\system\systemaction
 * @author 陈妙威
 */
abstract class PhabricatorSystemAction extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getActionConstant();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getScoreThreshold();

    /**
     * @param $actor
     * @param $score
     * @return bool
     * @author 陈妙威
     */
    public function shouldBlockActor($actor, $score)
    {
        return ($score > $this->getScoreThreshold());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLimitExplanation()
    {
        return \Yii::t("app",'You are performing too many actions too quickly.');
    }

    /**
     * @param $score
     * @return string
     * @author 陈妙威
     * @throws \Exception
     */
    public function getRateExplanation($score)
    {
        return \Yii::t("app",
            'The maximum allowed rate for this action is %s. You are taking ' .
            'actions at a rate of %s.',
            $this->formatRate($this->getScoreThreshold()),
            $this->formatRate($score));
    }

    /**
     * @param $rate
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    protected function formatRate($rate)
    {
        if ($rate > 10) {
            $str = \Yii::t("app",'%d / second', $rate);
        } else {
            $rate *= 60;
            if ($rate > 10) {
                $str = \Yii::t("app",'%d / minute', $rate);
            } else {
                $rate *= 60;
                $str = \Yii::t("app",'%d / hour', $rate);
            }
        }

        return phutil_tag('strong', array(), $str);
    }

}
