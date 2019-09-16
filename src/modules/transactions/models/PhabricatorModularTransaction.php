<?php

namespace orangins\modules\transactions\models;

// TODO: Some "final" modifiers have been VERY TEMPORARILY moved aside to
// allow DifferentialTransaction to extend this class without converting
// fully to ModularTransactions.
use orangins\modules\transactions\xaction\PhabricatorCoreTransactionType;
use PhutilClassMapQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\xaction\PhabricatorCoreVoidTransaction;

/**
 * Class PhabricatorModularTransaction
 * @package orangins\modules\transactions\models
 * @author 陈妙威
 */
abstract class PhabricatorModularTransaction extends PhabricatorApplicationTransaction
{

    /**
     * @var
     */
    private $implementation;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getBaseTransactionClass();

    /**
     * @return PhabricatorCoreVoidTransaction
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getModularType()
    {
        return $this->getTransactionImplementation();
    }

    /**
     * @return PhabricatorCoreVoidTransaction
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final protected function getTransactionImplementation()
    {
        if (!$this->implementation) {
            $this->implementation = $this->newTransactionImplementation();
        }

        return $this->implementation;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function newModularTransactionTypes() {
        $base_class = $this->getBaseTransactionClass();

        $types = (new PhutilClassMapQuery())
            ->setAncestorClass($base_class)
            ->setUniqueMethod('getTransactionTypeConstant')
            ->execute();

        // Add core transaction types.
        $types += (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorCoreTransactionType::class)
            ->setUniqueMethod('getTransactionTypeConstant')
            ->execute();

        return $types;
    }

    /**
     * @return PhabricatorCoreVoidTransaction
     * @author 陈妙威
     */
    private function newTransactionImplementation()
    {
        $types = $this->newModularTransactionTypes();

        $key = $this->getTransactionType();

        if (empty($types[$key])) {
            $type = $this->newFallbackModularTransactionType();
        } else {
            $type = clone $types[$key];
        }

        $type->setStorage($this);

        return $type;
    }

    /**
     * @return PhabricatorCoreVoidTransaction
     * @author 陈妙威
     */
    protected function newFallbackModularTransactionType()
    {
        return new PhabricatorCoreVoidTransaction();
    }

    /**
     * @param $object
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    final public function generateOldValue($object)
    {
        return $this->getTransactionImplementation()->generateOldValue($object);
    }

    /**
     * @param $object
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    final public function generateNewValue($object)
    {
        return $this->getTransactionImplementation()
            ->generateNewValue($object, $this->getNewValue());
    }

    /**
     * @param $object
     * @param array $xactions
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final public function willApplyTransactions($object, array $xactions)
    {
        return $this->getTransactionImplementation()
            ->willApplyTransactions($object, $xactions);
    }

    /**
     * @param $object
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final public function applyInternalEffects($object)
    {
        return $this->getTransactionImplementation()->applyInternalEffects($object);
    }

    /**
     * @param $object
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final public function applyExternalEffects($object)
    {
        return $this->getTransactionImplementation()->applyExternalEffects($object);
    }

    /* final */
    /**
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function shouldHide()
    {
        if ($this->getTransactionImplementation()->shouldHide()) {
            return true;
        }

        return parent::shouldHide();
    }

    /**
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    final public function shouldHideForFeed()
    {
        if ($this->getTransactionImplementation()->shouldHideForFeed()) {
            return true;
        }

        return parent::shouldHideForFeed();
    }

    /* final */
    /**
     * @param array $xactions
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function shouldHideForMail(array $xactions)
    {
        if ($this->getTransactionImplementation()->shouldHideForMail()) {
            return true;
        }

        return parent::shouldHideForMail($xactions);
    }

    /* final */
    /**
     * @return mixed
     * @throws \PhutilJSONParserException

     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getIcon()
    {
        $icon = $this->getTransactionImplementation()->getIcon();
        if ($icon !== null) {
            return $icon;
        }

        return parent::getIcon();
    }

    /* final */
    /**
     * @return mixed
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTitle()
    {
        $title = $this->getTransactionImplementation()->getTitle();
        if ($title !== null) {
            return $title;
        }

        return parent::getTitle();
    }

    /* final */
    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getActionName()
    {
        $action = $this->getTransactionImplementation()->getActionName();
        if ($action !== null) {
            return $action;
        }

        return parent::getActionName();
    }

    /* final */
    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getActionStrength()
    {
        $strength = $this->getTransactionImplementation()->getActionStrength();
        if ($strength !== null) {
            return $strength;
        }

        return parent::getActionStrength();
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function getTitleForMail()
    {
        $old_target = $this->getRenderingTarget();
        $new_target = self::TARGET_TEXT;
        $this->setRenderingTarget($new_target);
        $title = $this->getTitle();
        $this->setRenderingTarget($old_target);
        return $title;
    }

    /* final */
    /**
     * @return mixed
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        $title = $this->getTransactionImplementation()->getTitleForFeed();
        if ($title !== null) {
            return $title;
        }

        return parent::getTitleForFeed();
    }

    /* final */
    /**
     * @return mixed
     * @throws \PhutilJSONParserException

     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getColor()
    {
        $color = $this->getTransactionImplementation()->getColor();
        if ($color !== null) {
            return $color;
        }

        return parent::getColor();
    }

    /**
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function attachViewer(PhabricatorUser $viewer)
    {
        $this->getTransactionImplementation()->setViewer($viewer);
        return parent::attachViewer($viewer);
    }

    /**
     * @return bool
     * @throws \PhutilJSONParserException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final public function hasChangeDetails()
    {
        if ($this->getTransactionImplementation()->hasChangeDetailView()) {
            return true;
        }

        return parent::hasChangeDetails();
    }

    /**
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    final public function renderChangeDetails(PhabricatorUser $viewer)
    {
        $impl = $this->getTransactionImplementation();
        $impl->setViewer($viewer);
        $view = $impl->newChangeDetailView();
        if ($view !== null) {
            return $view;
        }

        return parent::renderChangeDetails($viewer);
    }

    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final protected function newRemarkupChanges()
    {
        return $this->getTransactionImplementation()->newRemarkupChanges();
    }

}
