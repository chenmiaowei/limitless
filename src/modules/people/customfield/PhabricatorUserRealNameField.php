<?php

namespace orangins\modules\people\customfield;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldGroup;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilJSONParserException;
use Yii;

/**
 * Class PhabricatorUserRealNameField
 * @package orangins\modules\people\customfield
 * @author 陈妙威
 */
final class PhabricatorUserRealNameField
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
        return 'user:realname';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModernFieldKey()
    {
        return 'realName';
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
        return Yii::t("app", 'Real Name');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getFieldDescription()
    {
        return Yii::t("app", 'Stores the real name of the user, like "Abraham Lincoln".');
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
     * @return void
     * @author 陈妙威
     */
    public function readValueFromObject(PhabricatorCustomFieldInterface $object)
    {
        $this->value = $object->getRealName();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getOldValueForApplicationTransactions()
    {
        return $this->getObject()->getRealName();
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function getNewValueForApplicationTransactions()
    {
        if (!$this->isEditable()) {
            return $this->getObject()->getRealName();
        }
        return $this->value;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function applyApplicationTransactionInternalEffects(
        PhabricatorApplicationTransaction $xaction)
    {
        $this->getObject()->setRealName($xaction->getNewValue());
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
     * @return $this|this
     * @author 陈妙威
     */
    public function setValueFromStorage($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param array $handles
     * @return AphrontFormTextControl
     * @throws Exception
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return (new AphrontFormTextControl())
            ->setName($this->getFieldKey())
            ->setValue($this->value)
            ->setLabel($this->getFieldName())
            ->setDisabled(!$this->isEditable());
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function isEditable()
    {
        return PhabricatorEnv::getEnvConfig('account.editable');
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
