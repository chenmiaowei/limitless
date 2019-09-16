<?php

namespace orangins\lib\infrastructure\standard;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;

/**
 * Class PhabricatorStandardCustomFieldTokenizer
 * @package orangins\lib\infrastructure\standard
 * @author 陈妙威
 */
abstract class PhabricatorStandardCustomFieldTokenizer
    extends PhabricatorStandardCustomFieldPHIDs
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getDatasource();

    /**
     * @param array $handles
     * @return mixed|AphrontFormTokenizerControl
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        $value = $this->getFieldValue();

        $control = (new AphrontFormTokenizerControl())
            ->setUser($this->getViewer())
            ->setLabel($this->getFieldName())
            ->setName($this->getFieldKey())
            ->setDatasource($this->getDatasource())
            ->setCaption($this->getCaption())
            ->setError($this->getFieldError())
            ->setValue(nonempty($value, array()));

        $limit = $this->getFieldConfigValue('limit');
        if ($limit) {
            $control->setLimit($limit);
        }

        return $control;
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

        $control = (new AphrontFormTokenizerControl())
            ->setLabel($this->getFieldName())
            ->setName($this->getFieldKey())
            ->setDatasource($this->newApplicationSearchDatasource())
            ->setValue(nonempty($value, array()));

        $form->appendControl($control);
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
        if ($value) {

            $datasource = $this->newApplicationSearchDatasource()
                ->setViewer($this->getViewer());
            $value = $datasource->evaluateTokens($value);

            $query->withApplicationSearchContainsConstraint(
                $this->newStringIndex(null),
                $value);
        }
    }

    /**
     * @param $condition
     * @return null|\orangins\lib\infrastructure\customfield\field\const
     * @author 陈妙威
     */
    public function getHeraldFieldValueType($condition)
    {
        return (new HeraldTokenizerFieldValue())
            ->setKey('custom.' . $this->getFieldKey())
            ->setDatasource($this->getDatasource());
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldFieldStandardType()
    {
        return HeraldField::STANDARD_PHID_LIST;
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getHeraldDatasource()
    {
        return $this->getDatasource();
    }

    /**
     * @return null|AphrontPHIDListHTTPParameterType
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontPHIDListHTTPParameterType();
    }

    /**
     * @return null|ConduitPHIDListParameterType
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        return new ConduitPHIDListParameterType();
    }

    /**
     * @return null|ConduitPHIDListParameterType
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitPHIDListParameterType();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        $datasource = $this->getDatasource();

        $limit = $this->getFieldConfigValue('limit');
        if ($limit) {
            $datasource->setLimit($limit);
        }

        return (new BulkTokenizerParameterType())
            ->setDatasource($datasource);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInHeraldActions()
    {
        return true;
    }

    /**
     * @return null|string
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return \Yii::t("app", 'Set "{0}" to', [
            $this->getFieldName()
        ]);
    }

    /**
     * @param $value
     * @return null|string
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function getHeraldActionDescription($value)
    {
        $list = $this->renderHeraldHandleList($value);
        return \Yii::t("app", 'Set "{0}" to: {1}.', [
            $this->getFieldName(), $list
        ]);
    }

    /**
     * @param $value
     * @return null|string
     * @author 陈妙威
     */
    public function getHeraldActionEffectDescription($value)
    {
        return $this->renderHeraldHandleList($value);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldActionStandardType()
    {
        return HeraldAction::STANDARD_PHID_LIST;
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getHeraldActionDatasource()
    {
        $datasource = $this->getDatasource();

        $limit = $this->getFieldConfigValue('limit');
        if ($limit) {
            $datasource->setLimit($limit);
        }

        return $datasource;
    }

    /**
     * @param $value
     * @return string
     * @author 陈妙威
     */
    private function renderHeraldHandleList($value)
    {
        if (!is_array($value)) {
            return \Yii::t("app", '(Invalid List)');
        } else {
            return $this->getViewer()
                ->renderHandleList($value)
                ->setAsInline(true)
                ->render();
        }
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newApplicationSearchDatasource()
    {
        $datasource = $this->getDatasource();

        return (new PhabricatorCustomFieldApplicationSearchDatasource())
            ->setDatasource($datasource);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newCommentAction()
    {
        $viewer = $this->getViewer();

        $datasource = $this->getDatasource()
            ->setViewer($viewer);

        $action = (new PhabricatorEditEngineTokenizerCommentAction())
            ->setDatasource($datasource);

        $limit = $this->getFieldConfigValue('limit');
        if ($limit) {
            $action->setLimit($limit);
        }

        $value = $this->getFieldValue();
        if ($value !== null) {
            $action->setInitialValue($value);
        }

        return $action;
    }

}
