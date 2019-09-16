<?php

namespace orangins\modules\dashboard\editors;

use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\icon\PhabricatorDashboardIconSet;
use orangins\modules\dashboard\layoutconfig\PhabricatorDashboardLayoutMode;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\xaction\dashboard\PhabricatorDashboardIconTransaction;
use orangins\modules\dashboard\xaction\dashboard\PhabricatorDashboardLayoutTransaction;
use orangins\modules\dashboard\xaction\dashboard\PhabricatorDashboardNameTransaction;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorIconSetEditField;
use orangins\modules\transactions\editfield\PhabricatorSelectEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * Class PhabricatorDashboardEditEngine
 * @package orangins\modules\dashboard\editors
 * @author 陈妙威
 */
final class PhabricatorDashboardEditEngine
    extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'dashboard';

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app",'Dashboards');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return \Yii::t("app",'Edit Dashboards');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app",'This engine is used to modify dashboards.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorDashboardApplication::className();
    }

    /**
     * @return mixed|\orangins\modules\transactions\editengine\PhabricatorEditEngineSubtypeInterface
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        $viewer = $this->getViewer();
        return PhabricatorDashboard::initializeNewDashboard($viewer);
    }

    /**
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\orangins\modules\dashboard\query\PhabricatorDashboardQuery
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        return PhabricatorDashboard::find();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app",'Create Dashboard');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateButtonText($object)
    {
        return \Yii::t("app",'Create Dashboard');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateCancelURI($object)
    {
        return '/dashboard/';
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app",'Edit Dashboard: {0}', [$object->getName()]);
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return \Yii::t("app",'Edit Dashboard');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return \Yii::t("app",'Create Dashboard');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app",'Dashboard');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $object->getURI();
    }

    /**
     * @param $object
     * @return array|\orangins\modules\transactions\editfield\PhabricatorEditField[]
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        $layout_options = PhabricatorDashboardLayoutMode::getLayoutModeMap();

        $fields = array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app",'Name'))
                ->setDescription(\Yii::t("app",'Name of the dashboard.'))
                ->setConduitDescription(\Yii::t("app",'Rename the dashboard.'))
                ->setConduitTypeDescription(\Yii::t("app",'New dashboard name.'))
                ->setTransactionType(
                    PhabricatorDashboardNameTransaction::TRANSACTIONTYPE)
                ->setIsRequired(true)
                ->setValue($object->getName()),
            (new PhabricatorIconSetEditField())
                ->setKey('icon')
                ->setLabel(\Yii::t("app",'Icon'))
                ->setTransactionType(
                    PhabricatorDashboardIconTransaction::TRANSACTIONTYPE)
                ->setIconSet(new PhabricatorDashboardIconSet())
                ->setDescription(\Yii::t("app",'Dashboard icon.'))
                ->setConduitDescription(\Yii::t("app",'Change the dashboard icon.'))
                ->setConduitTypeDescription(\Yii::t("app",'New dashboard icon.'))
                ->setValue($object->getIcon()),
            (new PhabricatorSelectEditField())
                ->setKey('layout')
                ->setLabel(\Yii::t("app",'Layout'))
                ->setDescription(\Yii::t("app",'Dashboard layout mode.'))
                ->setConduitDescription(\Yii::t("app",'Change the dashboard layout mode.'))
                ->setConduitTypeDescription(\Yii::t("app",'New dashboard layout mode.'))
                ->setTransactionType(
                    PhabricatorDashboardLayoutTransaction::TRANSACTIONTYPE)
                ->setOptions($layout_options)
                ->setValue($object->getRawLayoutMode()),
        );

        return $fields;
    }

}
