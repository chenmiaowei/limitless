<?php

namespace orangins\lib\infrastructure\customfield\exception;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use Exception;
use yii\base\UserException;

/**
 * Class PhabricatorCustomFieldNotProxyException
 * @package orangins\lib\infrastructure\customfield\exception
 * @author 陈妙威
 */
final class PhabricatorCustomFieldNotProxyException extends UserException
{

    /**
     * PhabricatorCustomFieldNotProxyException constructor.
     * @param PhabricatorCustomField $field
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function __construct(PhabricatorCustomField $field)
    {
        parent::__construct(
            \Yii::t("app",
                "Custom field '{0}' (with key '{1}', of class '{2}') can not have a " .
                "proxy set with {3}, because it returned {4} from {5}.",
                [
                    $field->getFieldName(),
                    $field->getFieldKey(),
                    get_class($field),
                    'setProxy()',
                    'false',
                    'canSetProxy()'
                ]));
    }
}
