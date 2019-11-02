<?php

namespace orangins\lib\infrastructure\customfield\standard;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\field\HeraldField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use PhutilSafeHTML;

/**
 * Class PhabricatorStandardCustomFieldLink
 * @package orangins\lib\infrastructure\customfield\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldLink
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'link';
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
            $indexes[] = $this->newStringIndex($value);
        }

        return $indexes;
    }

    /**
     * @param array $handles
     * @return mixed|PhutilSafeHTML|null
     * @throws Exception
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $value = $this->getFieldValue();

        if (!strlen($value)) {
            return null;
        }

        if (!PhabricatorEnv::isValidRemoteURIForLink($value)) {
            return $value;
        }

        return phutil_tag(
            'a',
            array(
                'href' => $value,
                'target' => '_blank',
                'rel' => 'noreferrer',
            ),
            $value);
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontRequest $request
     * @return array|mixed|string|void|null
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

        if (is_string($value) && !strlen($value)) {
            return;
        }

        $value = (array)$value;
        if ($value) {
            $query->withApplicationSearchContainsConstraint(
                $this->newStringIndex(null),
                $value);
        }
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontFormView $form
     * @param array $value
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
     * @return |null
     * @author 陈妙威
     */
    public function getHeraldFieldStandardType()
    {
        return HeraldField::STANDARD_TEXT;
    }

    /**
     * @return AphrontStringHTTPParameterType|null
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontStringHTTPParameterType();
    }

    /**
     * @return ConduitStringListParameterType|null
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        return new ConduitStringListParameterType();
    }

    /**
     * @return ConduitStringParameterType|null
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitStringParameterType();
    }

}
