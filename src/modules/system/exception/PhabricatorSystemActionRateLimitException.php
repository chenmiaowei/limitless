<?php

namespace orangins\modules\system\exception;

use orangins\modules\system\systemaction\PhabricatorSystemAction;
use yii\base\UserException;

/**
 * Class PhabricatorSystemActionRateLimitException
 * @package orangins\modules\system\exception
 * @author 陈妙威
 */
final class PhabricatorSystemActionRateLimitException extends UserException
{

    /**
     * @var PhabricatorSystemAction
     */
    private $action;
    /**
     * @var
     */
    private $score;

    /**
     * PhabricatorSystemActionRateLimitException constructor.
     * @param PhabricatorSystemAction $action
     * @param $score
     */
    public function __construct(PhabricatorSystemAction $action, $score)
    {
        $this->action = $action;
        $this->score = $score;
        parent::__construct($action->getLimitExplanation());
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function getRateExplanation()
    {
        return $this->action->getRateExplanation($this->score);
    }

}
