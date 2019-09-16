<?php

namespace orangins\lib\infrastructure\customfield\exception;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use yii\base\UserException;

/**
 * Class PhabricatorCustomFieldImplementationIncompleteException
 * @package orangins\lib\infrastructure\customfield\exception
 * @author 陈妙威
 */
final class PhabricatorCustomFieldImplementationIncompleteException extends UserException
{

    /**
     * PhabricatorCustomFieldImplementationIncompleteException constructor.
     * @param PhabricatorCustomField $field
     * @param bool $field_key_is_incomplete
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function __construct(
        PhabricatorCustomField $field,
        $field_key_is_incomplete = false)
    {

        if ($field_key_is_incomplete) {
            $key = \Yii::t("app",'<incomplete key>');
            $name = \Yii::t("app",'<incomplete name>');
        } else {
            $key = $field->getFieldKey();
            $name = $field->getFieldName();
        }

        parent::__construct(
            \Yii::t("app",
                "Custom field '{0}' (with key '{1}', of class '{2}') is incompletely " .
                "implemented: it claims to support a feature, but does not " .
                "implement all of the required methods for that feature.", [
                    $name,
                    $key,
                    get_class($field)
                ]));
    }

}
