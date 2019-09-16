<?php

namespace orangins\modules\metamta\models\exception;

use yii\base\UserException;

/**
 * Class PhabricatorMetaMTAReceivedMailProcessingException
 * @package orangins\modules\metamta\models\exception
 * @author 陈妙威
 */
final class PhabricatorMetaMTAReceivedMailProcessingException extends UserException
{

    /**
     * @var
     */
    private $statusCode;

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * PhabricatorMetaMTAReceivedMailProcessingException constructor.
     * @param $status_code
     */
    public function __construct($status_code /* ... */)
    {
        $args = func_get_args();
        $this->statusCode = $args[0];

        $args = array_slice($args, 1);
        call_user_func_array(array('parent', '__construct'), $args);
    }

}
