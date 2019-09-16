<?php

namespace orangins\modules\transactions\exception;

use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use yii\base\UserException;

/**
 * Class PhabricatorApplicationTransactionValidationException
 * @package orangins\modules\transactions\exception
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionValidationException extends UserException
{

    /**
     * @var \Exception[] array
     */
    private $errors;

    /**
     * PhabricatorApplicationTransactionValidationException constructor.
     * @param array $errors
     */
    public function __construct(array $errors)
    {
        assert_instances_of($errors, PhabricatorApplicationTransactionValidationError::class);

        $this->errors = $errors;

        $message = array();
        $message[] = \Yii::t("app", 'Validation errors:');
        foreach ($this->errors as $error) {
            $message[] = '  - ' . $error->getMessage();
        }

        parent::__construct(implode("\n", $message));
    }

    /**
     * @return \Exception[]
     * @author 陈妙威
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getErrorMessages()
    {
        return mpull($this->errors, 'getMessage');
    }

    /**
     * @param $type
     * @return null
     * @author 陈妙威
     */
    public function getShortMessage($type)
    {
        foreach ($this->errors as $error) {
            if ($error->getType() === $type) {
                if ($error->getShortMessage() !== null) {
                    return $error->getShortMessage();
                }
            }
        }
        return null;
    }

}
