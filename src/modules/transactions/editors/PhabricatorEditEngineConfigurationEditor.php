<?php

namespace orangins\modules\transactions\editors;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\application\PhabricatorTransactionsApplication;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;

/**
 * Class PhabricatorEditEngineConfigurationEditor
 * @package orangins\modules\transactions\editors
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorTransactionsApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app",'Edit Configurations');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorTransactions::TYPE_VIEW_POLICY;

        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_NAME;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_PREAMBLE;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_ORDER;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULT;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_LOCKS;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_SUBTYPE;
        $types[] =
            PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULTCREATE;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_ISEDIT;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_DISABLE;

        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_CREATEORDER;
        $types[] = PhabricatorEditEngineConfigurationTransaction::TYPE_EDITORDER;

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param $type
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function validateTransaction(
        ActiveRecordPHID $object,
        $type,
        array $xactions)
    {

        $errors = parent::validateTransaction($object, $type, $xactions);
        switch ($type) {
            case PhabricatorEditEngineConfigurationTransaction::TYPE_NAME:
                $missing = $this->validateIsEmptyTextField(
                    $object->getName(),
                    $xactions);

                if ($missing) {
                    $error = new PhabricatorApplicationTransactionValidationError(
                        $type,
                        \Yii::t("app",'Required'),
                        \Yii::t("app",'Form name is required.'),
                        nonempty(last($xactions), null));

                    $error->setIsMissingFieldError(true);
                    $errors[] = $error;
                }
                break;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_SUBTYPE:
                if ($xactions) {
                    $map = $object->getEngine()
                        ->setViewer($this->getActor())
                        ->newSubtypeMap();
                    foreach ($xactions as $xaction) {
                        $new = $xaction->getNewValue();

                        if (isset($map[$new])) {
                            continue;
                        }

                        $errors[] = new PhabricatorApplicationTransactionValidationError(
                            $type,
                            \Yii::t("app",'Invalid'),
                            \Yii::t("app",'Subtype "%s" is not a valid subtype.', $new),
                            $xaction);
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * @param ActiveRecordPHID|PhabricatorEditEngineConfiguration $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return int
     * @throws \PhutilJSONParserException

     * @author 陈妙威
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorEditEngineConfigurationTransaction::TYPE_NAME:
                return $object->getName();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_PREAMBLE;
                return $object->getPreamble();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ORDER:
                return $object->getFieldOrder();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULT:
                $field_key = $xaction->getMetadataValue('field.key');
                return $object->getFieldDefault($field_key);
            case PhabricatorEditEngineConfigurationTransaction::TYPE_LOCKS:
                return $object->getFieldLocks();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_SUBTYPE:
                return $object->getSubtype();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULTCREATE:
                return (int)$object->getIsDefault();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ISEDIT:
                return (int)$object->getIsEdit();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DISABLE:
                return (int)$object->getIsDisabled();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_CREATEORDER:
                return (int)$object->getCreateOrder();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_EDITORDER:
                return (int)$object->getEditOrder();

        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return int
     * @author 陈妙威
     * @throws \PhutilJSONParserException
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorEditEngineConfigurationTransaction::TYPE_NAME:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_PREAMBLE;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ORDER:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULT:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_LOCKS:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_SUBTYPE:
                return $xaction->getNewValue();
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULTCREATE:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ISEDIT:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DISABLE:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_CREATEORDER:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_EDITORDER:
                return (int)$xaction->getNewValue();
        }
    }

    /**
     * @param ActiveRecordPHID|PhabricatorEditEngineConfiguration $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorEditEngineConfigurationTransaction::TYPE_NAME:
                $object->setName($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_PREAMBLE;
                $object->setPreamble($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ORDER:
                $object->setFieldOrder($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULT:
                $field_key = $xaction->getMetadataValue('field.key');
                $object->setFieldDefault($field_key, $xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_LOCKS:
                $object->setFieldLocks($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_SUBTYPE:
                $object->setSubtype($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULTCREATE:
                $object->setIsDefault($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ISEDIT:
                $object->setIsEdit($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DISABLE:
                $object->setIsDisabled($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_CREATEORDER:
                $object->setCreateOrder($xaction->getNewValue());
                return;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_EDITORDER:
                $object->setEditOrder($xaction->getNewValue());
                return;
        }

        return parent::applyCustomInternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return void
     * @throws \Exception
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorEditEngineConfigurationTransaction::TYPE_NAME:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_PREAMBLE;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ORDER;
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULT:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_ISEDIT:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_LOCKS:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_SUBTYPE:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULTCREATE:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_DISABLE:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_CREATEORDER:
            case PhabricatorEditEngineConfigurationTransaction::TYPE_EDITORDER:
                return;
        }

        return parent::applyCustomExternalTransaction($object, $xaction);
    }

}
