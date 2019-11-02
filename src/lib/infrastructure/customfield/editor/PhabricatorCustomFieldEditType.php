<?php

namespace orangins\lib\infrastructure\customfield\editor;

use Exception;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\edittype\PhabricatorEditType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilJSONParserException;

/**
 * Class PhabricatorCustomFieldEditType
 * @package orangins\lib\infrastructure\customfield\editor
 * @author 陈妙威
 */
final class PhabricatorCustomFieldEditType
    extends PhabricatorEditType
{

    /**
     * @var
     */
    private $customField;

    /**
     * @param PhabricatorCustomField $custom_field
     * @return $this
     * @author 陈妙威
     */
    public function setCustomField(PhabricatorCustomField $custom_field)
    {
        $this->customField = $custom_field;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomField()
    {
        return $this->customField;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMetadata()
    {
        $field = $this->getCustomField();
        return parent::getMetadata() + $field->getApplicationTransactionMetadata();
    }

    /**
     * @param PhabricatorApplicationTransaction $template
     * @param array $spec
     * @return array|mixed
     * @throws PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    public function generateTransactions(
        PhabricatorApplicationTransaction $template,
        array $spec)
    {

        $value = idx($spec, 'value');

        /** @var PhabricatorApplicationTransaction $xaction */
        $xaction = $this->newTransaction($template)
            ->setNewValue($value);

        $custom_type = PhabricatorTransactions::TYPE_CUSTOMFIELD;
        if ($xaction->getTransactionType() == $custom_type) {
            $field = $this->getCustomField();

            $xaction
                ->setOldValue($field->getOldValueForApplicationTransactions())
                ->setMetadataValue('customfield:key', $field->getFieldKey());
        }

        return array($xaction);
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function getTransactionValueFromValue($value)
    {
        $field = $this->getCustomField();

        // Avoid changing the value of the field itself, since later calls would
        // incorrectly reflect the new value.
        $clone = clone $field;
        $clone->setValueFromApplicationTransactions($value);
        return $clone->getNewValueForApplicationTransactions();
    }

}
