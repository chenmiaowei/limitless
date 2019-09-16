<?php

namespace orangins\modules\transactions\exception;

use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\base\UserException;

/**
 * Class PhabricatorApplicationTransactionStructureException
 * @package orangins\modules\transactions\exception
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionStructureException extends UserException
{

    /**
     * PhabricatorApplicationTransactionStructureException constructor.
     * @param PhabricatorApplicationTransaction $xaction
     * @param $message
     */
    public function __construct(
        PhabricatorApplicationTransaction $xaction,
        $message)
    {

        $full_message = \Yii::t("app",
            'Attempting to apply a transaction (of class "{0}", with type "{1}") ' .
            'which has not been constructed correctly: {2}',
            [
                get_class($xaction),
                $xaction->getTransactionType(),
                $message
            ]);

        parent::__construct($full_message);
    }
}
