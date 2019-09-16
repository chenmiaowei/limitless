<?php

namespace orangins\modules\transactions\editors;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\search\models\PhabricatorEditEngineConfigurationQuery;
use orangins\modules\transactions\application\PhabricatorTransactionsApplication;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorRemarkupEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;

/**
 * Class PhabricatorEditEngineConfigurationEditEngine
 * @package orangins\modules\transactions\editors
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationEditEngine
    extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'transactions.editengine.config';

    /**
     * @var
     */
    private $targetEngine;

    /**
     * @param PhabricatorEditEngine $target_engine
     * @return $this
     * @author 陈妙威
     */
    public function setTargetEngine(PhabricatorEditEngine $target_engine)
    {
        $this->targetEngine = $target_engine;
        return $this;
    }

    /**
     * @return PhabricatorEditEngineConfigurationEditEngine
     * @author 陈妙威
     */
    public function getTargetEngine()
    {
        if (!$this->targetEngine) {
            // If we don't have a target engine, assume we're editing ourselves.
            return new PhabricatorEditEngineConfigurationEditEngine();
        }
        return $this->targetEngine;
    }

    /**
     * @return mixed|string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function getCreateNewObjectPolicy()
    {
        return $this->getTargetEngine()
            ->getApplication()
            ->getPolicy(PhabricatorPolicyCapability::CAN_EDIT);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app",'Edit Configurations');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return \Yii::t("app",'Configure Forms for Configuring Forms');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app",
            'Change how forms in other applications are created and edited. ' .
            'Advanced!');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorTransactionsApplication::className();
    }

    /**
     * @return PhabricatorEditEngineConfiguration|\orangins\modules\transactions\editengine\PhabricatorEditEngineSubtypeInterface
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return PhabricatorEditEngineConfiguration::initializeNewConfiguration(
            $this->getViewer(),
            $this->getTargetEngine());
    }

    /**
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorEditEngineConfigurationQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        return PhabricatorEditEngineConfiguration::find();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app",'Create New Form');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app",'Edit Form %d: %s', $object->getID(), $object->getDisplayName());
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return \Yii::t("app",'Form %d', $object->getID());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return \Yii::t("app",'Create Form');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app",'Form');
    }

    /**
     * @param $object
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        $id = $object->getID();
        return $this->getURI("view/{$id}/");
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getEditorURI()
    {
        return $this->getURI('edit/');
    }

    /**
     * @param $object
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getObjectCreateCancelURI($object)
    {
        return $this->getURI();
    }

    /**
     * @param null $path
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function getURI($path = null)
    {
        $engine_key = $this->getTargetEngine()->getEngineKey();
        return "/transactions/editengine/{$engine_key}/{$path}";
    }

    /**
     * @param $object
     * @return array|\orangins\modules\transactions\editfield\PhabricatorEditField[]
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        return array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app",'Name'))
                ->setDescription(\Yii::t("app",'Name of the form.'))
                ->setTransactionType(
                    PhabricatorEditEngineConfigurationTransaction::TYPE_NAME)
                ->setValue($object->getName()),
            (new PhabricatorRemarkupEditField())
                ->setKey('preamble')
                ->setLabel(\Yii::t("app",'Preamble'))
                ->setDescription(\Yii::t("app",'Optional instructions, shown above the form.'))
                ->setTransactionType(
                    PhabricatorEditEngineConfigurationTransaction::TYPE_PREAMBLE)
                ->setValue($object->getPreamble()),
        );
    }

}
