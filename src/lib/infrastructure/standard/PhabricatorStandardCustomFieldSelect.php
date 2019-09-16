<?php

namespace orangins\lib\infrastructure\standard;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontSelectHTTPParameterType;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\bulk\type\BulkSelectParameterType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorStandardCustomFieldSelect
 * @package orangins\lib\infrastructure\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldSelect
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'select';
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
     * @return array|\orangins\lib\infrastructure\customfield\field\array
     * @author 陈妙威
     */
    public function readApplicationSearchValueFromRequest(
        PhabricatorApplicationSearchEngine $engine,
        AphrontRequest $request)
    {
        return $request->getArr($this->getFieldKey());
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

        if (!is_array($value)) {
            $value = array();
        }
        $value = array_fuse($value);

        $control = (new AphrontFormCheckboxControl())
            ->setLabel($this->getFieldName());

        foreach ($this->getOptions() as $name => $option) {
            $control->addCheckbox(
                $this->getFieldKey() . '[]',
                $name,
                $option,
                isset($value[$name]));
        }

        $form->appendChild($control);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getOptions()
    {
        return $this->getFieldConfigValue('options', array());
    }

    /**
     * @param array $handles
     * @return mixed
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return (new AphrontFormSelectControl())
            ->setLabel($this->getFieldName())
            ->setCaption($this->getCaption())
            ->setName($this->getFieldKey())
            ->setValue($this->getFieldValue())
            ->setOptions($this->getOptions());
    }

    /**
     * @param array $handles
     * @return mixed|null
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        if (!strlen($this->getFieldValue())) {
            return null;
        }
        return ArrayHelper::getValue($this->getOptions(), $this->getFieldValue());
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws \PhutilJSONParserException
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitle(
        PhabricatorApplicationTransaction $xaction)
    {
        $author_phid = $xaction->getAuthorPHID();
        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        $old = ArrayHelper::getValue($this->getOptions(), $old, $old);
        $new = ArrayHelper::getValue($this->getOptions(), $new, $new);

        if (!$old) {
            return \Yii::t("app",
                '{0} set {1} to {2}.',
               [
                   $xaction->renderHandleLink($author_phid),
                   $this->getFieldName(),
                   $new
               ]);
        } else if (!$new) {
            return \Yii::t("app",
                '{0} removed {1}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName()
                ]);
        } else {
            return \Yii::t("app",
                '{0} changed {1} from {2} to {3}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $old,
                    $new
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
            HeraldAdapter::CONDITION_IS_ANY,
            HeraldAdapter::CONDITION_IS_NOT_ANY,
        );
    }

    /**
     * @param $condition
     * @return null|\orangins\lib\infrastructure\customfield\field\const
     * @author 陈妙威
     */
    public function getHeraldFieldValueType($condition)
    {
        $parameters = array(
            'object' => get_class($this->getObject()),
            'role' => PhabricatorCustomField::ROLE_HERALD,
            'key' => $this->getFieldKey(),
        );

        $datasource = (new PhabricatorStandardSelectCustomFieldDatasource())
            ->setParameters($parameters);

        return (new HeraldTokenizerFieldValue())
            ->setKey('custom.' . $this->getFieldKey())
            ->setDatasource($datasource)
            ->setValueMap($this->getOptions());
    }

    /**
     * @return null|AphrontSelectHTTPParameterType
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontSelectHTTPParameterType();
    }

    /**
     * @return null|ConduitStringListParameterType
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        return new ConduitStringListParameterType();
    }

    /**
     * @return null|ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return (new BulkSelectParameterType())
            ->setOptions($this->getOptions());
    }

}
