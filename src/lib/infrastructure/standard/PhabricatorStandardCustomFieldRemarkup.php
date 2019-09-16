<?php

namespace orangins\lib\infrastructure\standard;

use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\view\form\control\PhabricatorRemarkupControl;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorStandardCustomFieldRemarkup
 * @package orangins\lib\infrastructure\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldRemarkup
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'remarkup';
    }

    /**
     * @param array $handles
     * @return mixed|PhabricatorRemarkupControl
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return (new PhabricatorRemarkupControl())
            ->setUser($this->getViewer())
            ->setLabel($this->getFieldName())
            ->setName($this->getFieldKey())
            ->setCaption($this->getCaption())
            ->setValue($this->getFieldValue());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getStyleForPropertyView()
    {
        return 'block';
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function getApplicationTransactionRemarkupBlocks(
        PhabricatorApplicationTransaction $xaction)
    {
        return array(
            $xaction->getNewValue(),
        );
    }

    /**
     * @param array $handles
     * @return mixed|null|PHUIRemarkupView
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $value = $this->getFieldValue();

        if (!strlen($value)) {
            return null;
        }

        // TODO: Once this stabilizes, it would be nice to let fields batch this.
        // For now, an extra query here and there on object detail pages isn't the
        // end of the world.

        $viewer = $this->getViewer();
        return new PHUIRemarkupView($viewer, $value);
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitle(
        PhabricatorApplicationTransaction $xaction)
    {
        $author_phid = $xaction->getAuthorPHID();
        return \Yii::t("app",
            '{0} edited {1}.',
            [
                $xaction->renderHandleLink($author_phid),
                $this->getFieldName()
            ]);
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitleForFeed(
        PhabricatorApplicationTransaction $xaction)
    {
        $author_phid = $xaction->getAuthorPHID();
        $object_phid = $xaction->getObjectPHID();
        return \Yii::t("app",
            '{0} edited {1} on {2}.',
            [
                $xaction->renderHandleLink($author_phid),
                $this->getFieldName(),
                $xaction->renderHandleLink($object_phid)
            ]);
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @author 陈妙威
     */
    public function getApplicationTransactionHasChangeDetails(
        PhabricatorApplicationTransaction $xaction)
    {
        return true;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @param PhabricatorUser $viewer
     * @return null
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function getApplicationTransactionChangeDetails(
        PhabricatorApplicationTransaction $xaction,
        PhabricatorUser $viewer)
    {
        return $xaction->renderTextCorpusChangeDetails(
            $viewer,
            $xaction->getOldValue(),
            $xaction->getNewValue());
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
     * @return array|bool
     * @author 陈妙威
     */
    public function shouldAppearInApplicationSearch()
    {
        return false;
    }

    /**
     * @return null|ConduitStringParameterType
     * @author 陈妙威
     */
    public function getConduitEditParameterType()
    {
        return new ConduitStringParameterType();
    }

}
