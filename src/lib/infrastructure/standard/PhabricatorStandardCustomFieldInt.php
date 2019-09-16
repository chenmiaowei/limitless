<?php

namespace orangins\lib\infrastructure\standard;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorStandardCustomFieldInt
 * @package orangins\lib\infrastructure\standard
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
     * @return array|\orangins\lib\infrastructure\customfield\field\list
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
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
     * @return null|\orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldIndexStorage|\orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldNumericIndexStorage
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
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
     * @return \orangins\lib\infrastructure\customfield\field\this|PhabricatorStandardCustomField|PhabricatorStandardCustomFieldInt
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
     * @return mixed|null|\orangins\lib\infrastructure\customfield\field\array|string|void
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
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
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
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
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
     * @return array|\orangins\lib\infrastructure\customfield\field\list
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
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
                        \Yii::t("app", 'Invalid'),
                        \Yii::t("app", '{0} must be an integer.',[
                            $this->getFieldName()
                        ]),
                        $xaction);
                    $this->setFieldError(\Yii::t("app", 'Invalid'));
                }
            }
        }

        return $errors;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
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
