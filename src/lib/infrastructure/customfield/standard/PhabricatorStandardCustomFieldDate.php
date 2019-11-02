<?php

namespace orangins\lib\infrastructure\customfield\standard;

use Exception;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldNumericIndexStorage;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\time\PhabricatorTime;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormDateControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitEpochParameterType;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use ReflectionException;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorStandardCustomFieldDate
 * @package orangins\lib\infrastructure\customfield\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldDate
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'date';
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
     * @return PhabricatorCustomFieldNumericIndexStorage
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function buildOrderIndex()
    {
        return $this->newNumericIndex(0);
    }

    /**
     * @return int|mixed|null|string
     * @author 陈妙威
     */
    public function getValueForStorage()
    {
        $value = $this->getFieldValue();
        if (strlen($value)) {
            return (int)$value;
        } else {
            return null;
        }
    }

    /**
     * @param $value
     * @return PhabricatorStandardCustomField|PhabricatorStandardCustomFieldDate
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
     * @param array $handles
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return $this->newDateControl();
    }

    /**
     * @param AphrontRequest $request
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $control = $this->newDateControl();
        $control->setUser($request->getViewer());
        $value = $control->readValueFromRequest($request);

        $this->setFieldValue($value);
    }

    /**
     * @param array $handles
     * @return mixed|null
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $value = $this->getFieldValue();
        if (!$value) {
            return null;
        }

        return OranginsViewUtil::phabricator_datetime($value, $this->getViewer());
    }

    /**
     * @return AphrontFormDateControl
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    private function newDateControl()
    {
        $control = (new AphrontFormDateControl())
            ->setLabel($this->getFieldName())
            ->setName($this->getFieldKey())
            ->setUser($this->getViewer())
            ->setCaption($this->getCaption())
            ->setAllowNull(!$this->getRequired());

        // If the value is already numeric, treat it as an epoch timestamp and set
        // it directly. Otherwise, it's likely a field default, which we let users
        // specify as a string. Parse the string into an epoch.

        $value = $this->getFieldValue();
        if (!ctype_digit($value)) {
            $value = PhabricatorTime::parseLocalTime($value, $this->getViewer());
        }

        // If we don't have anything valid, make sure we pass `null`, since the
        // control special-cases that.
        $control->setValue(nonempty($value, null));

        return $control;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontRequest $request
     * @return array|array
     * @author 陈妙威
     */
    public function readApplicationSearchValueFromRequest(
        PhabricatorApplicationSearchEngine $engine,
        AphrontRequest $request)
    {

        $key = $this->getFieldKey();

        return array(
            'min' => $request->getStr($key . '.min'),
            'max' => $request->getStr($key . '.max'),
        );
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param PhabricatorCursorPagedPolicyAwareQuery $query
     * @param $value
     * @return mixed|void
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public function applyApplicationSearchConstraintToQuery(
        PhabricatorApplicationSearchEngine $engine,
        PhabricatorCursorPagedPolicyAwareQuery $query,
        $value)
    {

        $viewer = $this->getViewer();

        if (!is_array($value)) {
            $value = array();
        }

        $min_str = ArrayHelper::getValue($value, 'min', '');
        if (strlen($min_str)) {
            $min = PhabricatorTime::parseLocalTime($min_str, $viewer);
        } else {
            $min = null;
        }

        $max_str = ArrayHelper::getValue($value, 'max', '');
        if (strlen($max_str)) {
            $max = PhabricatorTime::parseLocalTime($max_str, $viewer);
        } else {
            $max = null;
        }

        if (($min !== null) || ($max !== null)) {
            $query->withApplicationSearchRangeConstraint(
                $this->newNumericIndex(null),
                $min,
                $max);
        }
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontFormView $form
     * @param $value
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @throws Exception
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

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(Yii::t("app", '{0} After', [
                        $this->getFieldName()
                    ]))
                    ->setName($this->getFieldKey() . '.min')
                    ->setValue(ArrayHelper::getValue($value, 'min', '')))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(Yii::t("app", '{0} Before', [
                        $this->getFieldName()
                    ]))
                    ->setName($this->getFieldKey() . '.max')
                    ->setValue(ArrayHelper::getValue($value, 'max', '')));
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws PhutilJSONParserException
     * @throws ReflectionException
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

        $viewer = $this->getViewer();

        $old_date = null;
        if ($old) {
            $old_date = OranginsViewUtil::phabricator_datetime($old, $viewer);
        }

        $new_date = null;
        if ($new) {
            $new_date = OranginsViewUtil::phabricator_datetime($new, $viewer);
        }

        if (!$old) {
            return Yii::t("app",
                '{0} set {1} to {2}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $new_date
                ]);
        } else if (!$new) {
            return Yii::t("app",
                '{0} removed {1}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName()
                ]);
        } else {
            return Yii::t("app",
                '{0} changed {1} from {2} to {3}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $old_date,
                    $new_date
                ]);
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitleForFeed(
        PhabricatorApplicationTransaction $xaction)
    {

        $viewer = $this->getViewer();

        $author_phid = $xaction->getAuthorPHID();
        $object_phid = $xaction->getObjectPHID();

        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        if (!$old) {
            return Yii::t("app",
                '{0} set {1} to {2} on {3}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    OranginsViewUtil::phabricator_datetime($new, $viewer),
                    $xaction->renderHandleLink($object_phid)
                ]);
        } else if (!$new) {
            return Yii::t("app",
                '{0} removed {1} on {2}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $xaction->renderHandleLink($object_phid)
                ]);
        } else {
            return Yii::t("app",
                '{0} changed {1} from {2} to {3} on {4}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    OranginsViewUtil::phabricator_datetime($old, $viewer),
                    OranginsViewUtil::phabricator_datetime($new, $viewer),
                    $xaction->renderHandleLink($object_phid)
                ]);
        }
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInConduitTransactions()
    {
        // TODO: Dates are complicated and we don't yet support handling them from
        // Conduit.
        return false;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        // TODO: Build a new "pair<epoch|null, epoch|null>" type or similar.
        return null;
    }

    /**
     * @return null|ConduitEpochParameterType
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitEpochParameterType();
    }

}
