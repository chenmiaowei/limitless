<?php

namespace orangins\lib\infrastructure\standard;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;

/**
 * Class PhabricatorStandardCustomFieldText
 * @package orangins\lib\infrastructure\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldText
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'text';
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
            $indexes[] = $this->newStringIndex($value);
        }

        return $indexes;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontRequest $request
     * @return mixed|null|\orangins\lib\infrastructure\customfield\field\array|string
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
                $this->newStringIndex(null),
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
            HeraldAdapter::CONDITION_CONTAINS,
            HeraldAdapter::CONDITION_NOT_CONTAINS,
            HeraldAdapter::CONDITION_IS,
            HeraldAdapter::CONDITION_IS_NOT,
            HeraldAdapter::CONDITION_REGEXP,
            HeraldAdapter::CONDITION_NOT_REGEXP,
        );
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldFieldStandardType()
    {
        return HeraldField::STANDARD_TEXT;
    }

    /**
     * @return null|AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontStringHTTPParameterType();
    }

    /**
     * @return null|ConduitStringParameterType
     * @author 陈妙威
     */
    public function getConduitEditParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return mixed|PhabricatorStringExportField
     * @author 陈妙威
     */
    protected function newExportFieldType()
    {
        return new PhabricatorStringExportField();
    }

}
