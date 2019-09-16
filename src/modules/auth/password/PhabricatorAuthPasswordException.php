<?php

namespace orangins\modules\auth\password;

use yii\base\UserException;

/**
 * Class PhabricatorAuthPasswordException
 * @package orangins\modules\auth\password
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordException extends UserException
{

    /**
     * @var
     */
    private $passwordError;
    /**
     * @var
     */
    private $confirmErorr;

    /**
     * PhabricatorAuthPasswordException constructor.
     * @param $message
     * @param $password_error
     * @param null $confirm_error
     */
    public function __construct(
        $message,
        $password_error,
        $confirm_error = null)
    {

        $this->passwordError = $password_error;
        $this->confirmError = $confirm_error;

        parent::__construct($message);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPasswordError()
    {
        return $this->passwordError;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConfirmError()
    {
        return $this->confirmError;
    }

}
