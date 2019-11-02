<?php

namespace orangins\lib\infrastructure\customfield\standard;

use orangins\lib\export\field\PhabricatorIntExportField;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldIndexStorage;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldNumericIndexStorage;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontIntHTTPParameterType;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitIntParameterType;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Yii;
use yii\base\Exception;

/**
 * Class PhabricatorStandardCustomFieldInt
 * @package orangins\lib\infrastructure\customfield\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldInt
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'int';
    }

    /**
     * @return array
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function buildFieldIndexes()
    {
        $indexes = array();

        $value = $this->getFieldValue();
        if (strlen($value)) {
            $indexes[] = $this->newNumericIndex((int)$value);
        }

        return $indexes;
    }

    /**
     * @return null|PhabricatorCustomFieldIndexStorage|PhabricatorCustomFieldNumericIndexStorage
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function buildOrderIndex()
    {
        return $this->newNumericIndex(0);
    }

    /**
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getValueForStorage()
    {
        $value = $this->getFieldValue();
        if (strlen($value)) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param $value
     * @return PhabricatorStandardCustomField|PhabricatorStandardCustomFieldInt
     * @author 陈妙威
     */
    public function setValueFromStorage($value)
    {
        if (strlen($value)) {
            $value = (int)$value;
        } else {
            $value = null;
        }
        return $this->setFieldValue($value);
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontRequest $request
     * @return mixed|null|array|string|void
     * @author 陈妙威
     */
    public function readApplicationSearchValueFromRequest(
        PhabricatorApplicationSearchEngine $engine,
        AphrontRequest $request)
    {

        return $request->getStr($this->getFieldKey());
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param PhabricatorCursorPagedPolicyAwareQuery $query
     * @param $value
     * @return mixed|void
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function applyApplicationSearchConstraintToQuery(
        PhabricatorApplicationSearchEngine $engine,
        PhabricatorCursorPagedPolicyAwareQuery $query,
        $value)
    {

        if (strlen($value)) {
            $query->withApplicationSearchContainsConstraint(
                $this->newNumericIndex(null),
                $value);
        }
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontFormView $form
     * @param $value
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function appendToApplicationSearchForm(
        PhabricatorApplicationSearchEngine $engine,
        AphrontFormView $form,
        $value)
    {

        $form->appendChild(
            (new AphrontFormTextControl())
                ->setLabel($this->getFieldName())
                ->setName($this->getFieldKey())
                ->setValue($value));
    }

    /**
     * @param PhabricatorApplicationTransactionEditor $editor
     * @param $type
     * @param array $xactions
     * @return array
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function validateApplicationTransactions(
        PhabricatorApplicationTransactionEditor $editor,
        $type,
        array $xactions)
    {

        $errors = parent::validateApplicationTransactions(
            $editor,
            $type,
            $xactions);

        foreach ($xactions as $xaction) {
            $value = $xaction->getNewValue();
            if (strlen($value)) {
                if (!preg_match('/^-?\d+/', $value)) {
                    $errors[] = new PhabricatorApplicationTransactionValidationError(
                        $type,
                        Yii::t("app", 'Invalid'),
                        Yii::t("app", '{0} must be an integer.', [
                            $this->getFieldName()
                        ]),
                        $xaction);
                    $this->setFieldError(Yii::t("app", 'Invalid'));
                }
            }
        }

        return $errors;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function getApplicationTransactionHasEffect(
        PhabricatorApplicationTransaction $xaction)
    {

        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();
        if (!strlen($old) && strlen($new)) {
            return true;
        } else if (strlen($old) && !strlen($new)) {
            return true;
        } else {
            return ((int)$old !== (int)$new);
        }
    }

    /**
     * @return null|AphrontIntHTTPParameterType
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontIntHTTPParameterType();
    }

    /**
     * @return null|ConduitIntParameterType
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        return new ConduitIntParameterType();
    }

    /**
     * @return null|ConduitIntParameterType
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitIntParameterType();
    }

    /**
     * @return mixed|PhabricatorIntExportField
     * @author 陈妙威
     */
    protected function newExportFieldType()
    {
        return new PhabricatorIntExportField();
    }

}
