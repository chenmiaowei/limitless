<?php

namespace orangins\lib\infrastructure\standard;

use Exception;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldIndexStorage;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldNumericIndexStorage;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontBoolHTTPParameterType;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\conduit\parametertype\ConduitBoolParameterType;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\field\HeraldField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilJSONParserException;
use Yii;

/**
 * Class PhabricatorStandardCustomFieldBool
 * @package orangins\lib\infrastructure\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldBool
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'bool';
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
     * @return PhabricatorCustomFieldIndexStorage|PhabricatorCustomFieldNumericIndexStorage|null
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function buildOrderIndex()
    {
        return $this->newNumericIndex(0);
    }

    /**
     * @param AphrontRequest $request
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $this->setFieldValue((bool)$request->getBool($this->getFieldKey()));
    }

    /**
     * @return int|mixed|string|null
     * @author 陈妙威
     */
    public function getValueForStorage()
    {
        $value = $this->getFieldValue();
        if ($value !== null) {
            return (int)$value;
        } else {
            return null;
        }
    }

    /**
     * @param $value
     * @return PhabricatorStandardCustomField|PhabricatorStandardCustomFieldBool
     * @author 陈妙威
     */
    public function setValueFromStorage($value)
    {
        if (strlen($value)) {
            $value = (bool)$value;
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
        if ($value == 'require') {
            $query->withApplicationSearchContainsConstraint(
                $this->newNumericIndex(null),
                1);
        }
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontFormView $form
     * @param $value
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function appendToApplicationSearchForm(
        PhabricatorApplicationSearchEngine $engine,
        AphrontFormView $form,
        $value)
    {

        $form->appendChild(
            (new AphrontFormSelectControl())
                ->setLabel($this->getFieldName())
                ->setName($this->getFieldKey())
                ->setValue($value)
                ->setOptions(
                    array(
                        '' => $this->getString('search.default', Yii::t("app", '(Any)')),
                        'require' => $this->getString('search.require', Yii::t("app", 'Require')),
                    )));
    }

    /**
     * @param array $handles
     * @return mixed|AphrontFormCheckboxControl
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return (new AphrontFormCheckboxControl())
            ->setLabel($this->getFieldName())
            ->setCaption($this->getCaption())
            ->addCheckbox(
                $this->getFieldKey(),
                1,
                $this->getString('edit.checkbox'),
                (bool)$this->getFieldValue());
    }

    /**
     * @param array $handles
     * @return array|mixed|null
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $value = $this->getFieldValue();
        if ($value) {
            return $this->getString('view.yes', Yii::t("app", 'Yes'));
        } else {
            return null;
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws PhutilJSONParserException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitle(
        PhabricatorApplicationTransaction $xaction)
    {
        $author_phid = $xaction->getAuthorPHID();
        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        if ($new) {
            return Yii::t("app",
                '{0} checked {1}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName()
                ]);
        } else {
            return Yii::t("app",
                '{0} unchecked {1}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName()
                ]);
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInHerald()
    {
        return true;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getHeraldFieldConditions()
    {
        return array(
            HeraldAdapter::CONDITION_IS_TRUE,
            HeraldAdapter::CONDITION_IS_FALSE,
        );
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getHeraldFieldStandardType()
    {
        return HeraldField::STANDARD_BOOL;
    }

    /**
     * @return AphrontBoolHTTPParameterType|null
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontBoolHTTPParameterType();
    }

    /**
     * @return ConduitBoolParameterType|null
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        return new ConduitBoolParameterType();
    }

    /**
     * @return ConduitBoolParameterType|null
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitBoolParameterType();
    }

}
