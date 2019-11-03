<?php

namespace orangins\modules\people\customfield;

use Exception;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilJSONParserException;
use Yii;

/**
 * Class PhabricatorUserTitleField
 * @package orangins\modules\people\customfield
 * @author 陈妙威
 */
final class PhabricatorUserTitleField extends PhabricatorUserCustomField
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
        return 'user:title';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModernFieldKey()
    {
        return 'title';
    }

//    public function shouldAppearInApplicationSearch()
//    {
//        return true;
//    }
//
//    public function buildFieldIndexes()
//    {
//        $indexes = array();
//
//        $value = $this->value;
//        if (strlen($value)) {
//            $indexes[] = $this->newStringIndex($value);
//        }
//
//        return $indexes;
//    }
//
//    protected function newStringIndexStorage()
//    {
//        return new PhabricatorUserCustomFieldStringIndex();
//    }

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
        return Yii::t("app", 'Title');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getFieldDescription()
    {
        return Yii::t("app", 'User title, like "CEO" or "Assistant to the Manager".');
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
     * @param PhabricatorCustomFieldInterface|PhabricatorUser $object
     * @return PhabricatorCustomField|void
     * @throws Exception
     * @author 陈妙威
     */
    public function readValueFromObject(PhabricatorCustomFieldInterface $object)
    {
        $this->value = $object->loadUserProfile()->getTitle();
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */

    public function getOldValueForApplicationTransactions()
    {
        /** @var PhabricatorUser $phabricatorCustomField */
        $phabricatorCustomField = $this->getObject();
        return $phabricatorCustomField->loadUserProfile()->getTitle();
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
     * @throws PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    public function applyApplicationTransactionInternalEffects(
        PhabricatorApplicationTransaction $xaction)
    {
        /** @var PhabricatorUser $phabricatorCustomField */
        $phabricatorCustomField = $this->getObject();
        $title = $xaction->getNewValue();
        $phabricatorCustomField->loadUserProfile()->setTitle($title);
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
     * @return $this
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
        return (new AphrontFormTextControl())
            ->setName($this->getFieldKey())
            ->setValue($this->value)
            ->setLabel($this->getFieldName());
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
