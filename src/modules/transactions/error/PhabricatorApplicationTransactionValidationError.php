<?php

namespace orangins\modules\transactions\error;

use orangins\lib\OranginsObject;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorApplicationTransactionValidationError
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionValidationError
    extends OranginsObject
{

    /**
     * @var
     */
    private $type;
    /**
     * @var PhabricatorApplicationTransaction
     */
    private $transaction;
    /**
     * @var
     */
    private $shortMessage;
    /**
     * @var
     */
    private $message;
    /**
     * @var
     */
    private $isMissingFieldError;

    /**
     * PhabricatorApplicationTransactionValidationError constructor.
     * @param $type
     * @param $short_message
     * @param $message
     * @param PhabricatorApplicationTransaction|null $xaction
     */
    public function __construct(
        $type,
        $short_message,
        $message,
        PhabricatorApplicationTransaction $xaction = null)
    {

        $this->type = $type;
        $this->shortMessage = $short_message;
        $this->message = $message;
        $this->transaction = $xaction;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return PhabricatorApplicationTransaction|null
     * @author 陈妙威
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getShortMessage()
    {
        return $this->shortMessage;
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
     * @param $is_missing_field_error
     * @return $this
     * @author 陈妙威
     */
    public function setIsMissingFieldError($is_missing_field_error)
    {
        $this->isMissingFieldError = $is_missing_field_error;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsMissingFieldError()
    {
        return $this->isMissingFieldError;
    }

}
