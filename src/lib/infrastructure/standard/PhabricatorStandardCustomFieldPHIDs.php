<?php

namespace orangins\lib\infrastructure\standard;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Common code for standard field types which store lists of PHIDs.
 */
abstract class PhabricatorStandardCustomFieldPHIDs
    extends PhabricatorStandardCustomField
{

    /**
     * @return array|\orangins\lib\infrastructure\customfield\field\list
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function buildFieldIndexes()
    {
        $indexes = array();

        $value = $this->getFieldValue();
        if (is_array($value)) {
            foreach ($value as $phid) {
                $indexes[] = $this->newStringIndex($phid);
            }
        }

        return $indexes;
    }

    /**
     * @param AphrontRequest $request
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $value = $request->getArr($this->getFieldKey());
        $this->setFieldValue($value);
    }

    /**
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getValueForStorage()
    {
        $value = $this->getFieldValue();
        if (!$value) {
            return null;
        }

        return json_encode(array_values($value));
    }

    /**
     * @param $value
     * @return $this|\orangins\lib\infrastructure\customfield\field\this|PhabricatorStandardCustomField
     * @author 陈妙威
     */
    public function setValueFromStorage($value)
    {
        // NOTE: We're accepting either a JSON string (a real storage value) or
        // an array (from HTTP parameter prefilling). This is a little hacky, but
        // should hold until this can get cleaned up more thoroughly.
        // TODO: Clean this up.

        $result = array();
        if (!is_array($value)) {
            $value = json_decode($value, true);
            if (is_array($value)) {
                $result = array_values($value);
            }
        }

        $this->setFieldValue($value);

        return $this;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontRequest $request
     * @return array|\orangins\lib\infrastructure\customfield\field\array|void
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
     * @return array|mixed
     * @author 陈妙威
     */
    public function getRequiredHandlePHIDsForPropertyView()
    {
        $value = $this->getFieldValue();
        if ($value) {
            return $value;
        }
        return array();
    }

    /**
     * @param array $handles
     * @return array|\dict|mixed|null|\PhutilSafeHTML
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $value = $this->getFieldValue();
        if (!$value) {
            return null;
        }

        $handles = mpull($handles, 'renderHovercardLink');
        $handles = phutil_implode_html(', ', $handles);
        return $handles;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getRequiredHandlePHIDsForEdit()
    {
        $value = $this->getFieldValue();
        if ($value) {
            return $value;
        } else {
            return array();
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function getApplicationTransactionRequiredHandlePHIDs(
        PhabricatorApplicationTransaction $xaction)
    {

        $old = $this->decodeValue($xaction->getOldValue());
        $new = $this->decodeValue($xaction->getNewValue());

        $add = array_diff($new, $old);
        $rem = array_diff($old, $new);

        return array_merge($add, $rem);
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

        $old = $this->decodeValue($xaction->getOldValue());
        $new = $this->decodeValue($xaction->getNewValue());

        $add = array_diff($new, $old);
        $rem = array_diff($old, $new);

        if ($add && !$rem) {
            return \Yii::t("app",
                '{0} updated {1}, added {2}: {3}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    phutil_count($add),
                    $xaction->renderHandleList($add)
                ]);
        } else if ($rem && !$add) {
            return \Yii::t("app",
                '{0} updated {1}, removed {2}: {3}.',
               [
                   $xaction->renderHandleLink($author_phid),
                   $this->getFieldName(),
                   phutil_count($rem),
                   $xaction->renderHandleList($rem)
               ]);
        } else {
            return \Yii::t("app",
                '{0} updated {1}, added {2}: {3}; removed {4}: {5}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    phutil_count($add),
                    $xaction->renderHandleList($add),
                    phutil_count($rem),
                    $xaction->renderHandleList($rem)
                ]);
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws \PhutilJSONParserException
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitleForFeed(
        PhabricatorApplicationTransaction $xaction)
    {
        $author_phid = $xaction->getAuthorPHID();
        $object_phid = $xaction->getObjectPHID();

        $old = $this->decodeValue($xaction->getOldValue());
        $new = $this->decodeValue($xaction->getNewValue());

        $add = array_diff($new, $old);
        $rem = array_diff($old, $new);

        if ($add && !$rem) {
            return \Yii::t("app",
                '{0} updated {1} for {2}, added {3}: {4}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $xaction->renderHandleLink($object_phid),
                    phutil_count($add),
                    $xaction->renderHandleList($add)
                ]);
        } else if ($rem && !$add) {
            return \Yii::t("app",
                '{0} updated {1} for {2}, removed {3}: {4}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $xaction->renderHandleLink($object_phid),
                    phutil_count($rem),
                    $xaction->renderHandleList($rem)
                ]);
        } else {
            return \Yii::t("app",
                '{0} updated {1} for {2}, added {3}: {4}; removed {5}: {6}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $xaction->renderHandleLink($object_phid),
                    phutil_count($add),
                    $xaction->renderHandleList($add),
                    phutil_count($rem),
                    $xaction->renderHandleList($rem)
                ]);
        }
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

        // If the user is adding PHIDs, make sure the new PHIDs are valid and
        // visible to the actor. It's OK for a user to edit a field which includes
        // some invalid or restricted values, but they can't add new ones.

        foreach ($xactions as $xaction) {
            $old = $this->decodeValue($xaction->getOldValue());
            $new = $this->decodeValue($xaction->getNewValue());

            $add = array_diff($new, $old);

            $invalid = PhabricatorObjectQuery::loadInvalidPHIDsForViewer(
                $editor->getActor(),
                $add);

            if ($invalid) {
                $error = new PhabricatorApplicationTransactionValidationError(
                    $type,
                    \Yii::t("app", 'Invalid'),
                    \Yii::t("app",
                        'Some of the selected PHIDs in field "{0}" are invalid or ' .
                        'restricted: {1}.',
                       [
                           $this->getFieldName(),
                           implode(', ', $invalid)
                       ]),
                    $xaction);
                $errors[] = $error;
                $this->setFieldError(\Yii::t("app", 'Invalid'));
            }
        }

        return $errors;
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
            HeraldAdapter::CONDITION_INCLUDE_ALL,
            HeraldAdapter::CONDITION_INCLUDE_ANY,
            HeraldAdapter::CONDITION_INCLUDE_NONE,
            HeraldAdapter::CONDITION_EXISTS,
            HeraldAdapter::CONDITION_NOT_EXISTS,
        );
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldFieldStandardType()
    {
        return HeraldField::STANDARD_PHID_NULLABLE;
    }

    /**
     * @return array|mixed|\orangins\lib\infrastructure\customfield\field\array
     * @author 陈妙威
     */
    public function getHeraldFieldValue()
    {
        // If the field has a `null` value, make sure we hand an `array()` to
        // Herald.
        $value = parent::getHeraldFieldValue();
        if ($value) {
            return $value;
        }
        return array();
    }

    /**
     * @param $value
     * @return array|mixed
     * @author 陈妙威
     */
    protected function decodeValue($value)
    {
        $value = json_decode($value);
        if (!is_array($value)) {
            $value = array();
        }

        return $value;
    }

    /**
     * @return null|AphrontPHIDListHTTPParameterType
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontPHIDListHTTPParameterType();
    }

}
