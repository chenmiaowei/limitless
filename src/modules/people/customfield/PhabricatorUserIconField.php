<?php

namespace orangins\modules\people\customfield;

use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\PHUIFormIconSetControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\people\iconset\PhabricatorPeopleIconSet;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorUserIconField
 * @package orangins\modules\people\customfield
 * @author 陈妙威
 */
final class PhabricatorUserIconField
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
        return 'user:icon';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModernFieldKey()
    {
        return 'icon';
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
        return \Yii::t("app", 'Icon');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getFieldDescription()
    {
        return \Yii::t("app", 'User icon to accompany their title.');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canDisableField()
    {
        return false;
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
     * @param PhabricatorCustomFieldInterface $object
     * @return \orangins\lib\infrastructure\customfield\field\PhabricatorCustomField|void
     * @author 陈妙威
     */
    public function readValueFromObject(PhabricatorCustomFieldInterface $object)
    {
        $this->value = $object->loadUserProfile()->getIcon();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getOldValueForApplicationTransactions()
    {
        return $this->getObject()->loadUserProfile()->getIcon();
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
        $this->getObject()->loadUserProfile()->setIcon($xaction->getNewValue());
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
     * @return PHUIFormIconSetControl
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return (new PHUIFormIconSetControl())
            ->setName($this->getFieldKey())
            ->setValue($this->value)
            ->setLabel($this->getFieldName())
            ->setIconSet(new PhabricatorPeopleIconSet());
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
