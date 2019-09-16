<?php

namespace orangins\lib\infrastructure\customfield\exception;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use yii\base\UserException;

/**
 * Class PhabricatorCustomFieldDataNotAvailableException
 * @package orangins\lib\infrastructure\customfield\exception
 * @author 陈妙威
 */
final class PhabricatorCustomFieldDataNotAvailableException extends UserException
{

    /**
     * PhabricatorCustomFieldDataNotAvailableException constructor.
     * @param PhabricatorCustomField $field
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function __construct(PhabricatorCustomField $field)
    {
        parent::__construct(
            \Yii::t("app",
                "Custom field '{0}' (with key '{1}', of class '{2}') is attempting " .
                "to access data which is not available in this context.", [
                    $field->getFieldName(),
                    $field->getFieldKey(),
                    get_class($field)
                ]));
    }

}
