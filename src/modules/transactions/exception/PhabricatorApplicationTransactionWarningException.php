<?php

namespace orangins\modules\transactions\exception;

use yii\base\UserException;

/**
 * Class PhabricatorApplicationTransactionWarningException
 * @package orangins\modules\transactions\exception
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionWarningException extends UserException
{

    /**
     * @var array
     */
    private $xactions;

    /**
     * PhabricatorApplicationTransactionWarningException constructor.
     * @param array $xactions
     */
    public function __construct(array $xactions)
    {
        $this->xactions = $xactions;
        parent::__construct();
    }

}
