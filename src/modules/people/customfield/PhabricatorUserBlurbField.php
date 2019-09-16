<?php

namespace orangins\modules\people\customfield;

use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\PhabricatorRemarkupControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorUserBlurbField
 * @package orangins\modules\people\customfield
 * @author 陈妙威
 */
final class PhabricatorUserBlurbField
    extends PhabricatorUserCustomField
{

    /**
     * @var
     */
    private $value;

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldKey()
    {
        return 'user:blurb';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModernFieldKey()
    {
        return 'blurb';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldKeyForConduit()
    {
        return $this->getModernFieldKey();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldName()
    {
        return \Yii::t("app", 'Blurb');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getFieldDescription()
    {
        return \Yii::t("app", 'Short blurb about the user.');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInApplicationTransactions()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInEditView()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInPropertyView()
    {
        return true;
    }

    /**
     * @param PhabricatorCustomFieldInterface $object
     * @return \orangins\lib\infrastructure\customfield\field\PhabricatorCustomField|void
     * @author 陈妙威
     */
    public function readValueFromObject(PhabricatorCustomFieldInterface $object)
    {
        $this->value = $object->loadUserProfile()->getBlurb();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getOldValueForApplicationTransactions()
    {
        return $this->getObject()->loadUserProfile()->getBlurb();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getNewValueForApplicationTransactions()
    {
        return $this->value;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @author 陈妙威
     */
    public function applyApplicationTransactionInternalEffects(
        PhabricatorApplicationTransaction $xaction)
    {
        $this->getObject()->loadUserProfile()->setBlurb($xaction->getNewValue());
    }

    /**
     * @param AphrontRequest $request
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $this->value = $request->getStr($this->getFieldKey());
    }

    /**
     * @param $value
     * @return $this|\orangins\lib\infrastructure\customfield\field\this
     * @author 陈妙威
     */
    public function setValueFromStorage($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param array $handles
     * @return mixed
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return (new PhabricatorRemarkupControl())
            ->setUser($this->getViewer())
            ->setName($this->getFieldKey())
            ->setValue($this->value)
            ->setLabel($this->getFieldName());
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
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
     * @return null|string
     * @author 陈妙威
     */
    public function renderPropertyViewLabel()
    {
        return null;
    }

    /**
     * @param array $handles
     * @return PHUIRemarkupView|null
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $blurb = $this->getObject()->loadUserProfile()->getBlurb();
        if (!strlen($blurb)) {
            return null;
        }

        $viewer = $this->getViewer();
        $view = new PHUIRemarkupView($viewer, $blurb);

        return $view;
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
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInConduitTransactions()
    {
        return true;
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
