<?php

namespace orangins\modules\dashboard\editors;

use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\query\PhabricatorDashboardPanelQuery;
use orangins\modules\dashboard\xaction\dashboard\PhabricatorDashboardPanelsTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardPanelNameTransaction;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * Class PhabricatorDashboardPanelEditEngine
 * @package orangins\modules\dashboard\editors
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelEditEngine
    extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'dashboard.panel';

    /**
     * @var
     */
    private $panelType;
    /**
     * @var
     */
    private $contextObject;
    /**
     * @var
     */
    private $columnKey;

    /**
     * @param $panel_type
     * @return $this
     * @author 陈妙威
     */
    public function setPanelType($panel_type)
    {
        $this->panelType = $panel_type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanelType()
    {
        return $this->panelType;
    }

    /**
     * @param $context
     * @return $this
     * @author 陈妙威
     */
    public function setContextObject($context)
    {
        $this->contextObject = $context;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContextObject()
    {
        return $this->contextObject;
    }

    /**
     * @param $column_key
     * @return $this
     * @author 陈妙威
     */
    public function setColumnKey($column_key)
    {
        $this->columnKey = $column_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getColumnKey()
    {
        return $this->columnKey;
    }

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
        return pht('Dashboard Panels');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return pht('Edit Dashboard Panels');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function supportsSearch()
    {
        return true;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return pht('This engine is used to modify dashboard panels.');
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
        $panel = PhabricatorDashboardPanel::initializeNewPanel($viewer);

        if ($this->panelType) {
            $panel->setPanelType($this->panelType);
        }

        return $panel;
    }

    /**
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorDashboardPanelQuery
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        return PhabricatorDashboardPanel::find();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return pht('Create Dashboard Panel');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateButtonText($object)
    {
        return pht('Create Panel');
    }

    /**
     * @param $object
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getObjectCreateCancelURI($object)
    {
        $context = $this->getContextObject();
        if ($context) {
            return $context->getURI();
        }

        return parent::getObjectCreateCancelURI($object);
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getEffectiveObjectEditDoneURI($object)
    {
        $context = $this->getContextObject();
        if ($context) {
            return $context->getURI();
        }

        return parent::getEffectiveObjectEditDoneURI($object);
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditCancelURI($object)
    {
        $context = $this->getContextObject();
        if ($context) {
            return $context->getURI();
        }

        return parent::getObjectEditCancelURI($object);
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app", 'Edit Panel: {0}', [$object->getName()]);
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return pht('Edit Panel');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return pht('Edit Panel');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return pht('Dashboard Panel');
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
     * @param array $xactions
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function didApplyTransactions($object, array $xactions)
    {
        $context = $this->getContextObject();

        if ($context instanceof PhabricatorDashboard) {
            // Only add the panel to the dashboard when we're creating a new panel,
            // not if we're editing an existing panel.
            if (!$this->getIsCreate()) {
                return;
            }

            $viewer = $this->getViewer();
            $controller = $this->getAction();
            $request = $controller->getRequest();

            $dashboard = $context;

            $xactions = array();

            $ref_list = clone $dashboard->getPanelRefList();

            $ref_list->newPanelRef($object, $this->getColumnKey());
            $new_panels = $ref_list->toDictionary();

            $xactions[] = $dashboard->getApplicationTransactionTemplate()
                ->setTransactionType(
                    PhabricatorDashboardPanelsTransaction::TRANSACTIONTYPE)
                ->setNewValue($new_panels);

            $editor = $dashboard->getApplicationTransactionEditor()
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true);

            $editor->applyTransactions($dashboard, $xactions);
        }
    }

    /**
     * @param PhabricatorDashboardPanel $object
     * @return array|\orangins\modules\transactions\editfield\PhabricatorEditField[]
     * @author 陈妙威
     * @throws \Exception
     */
    protected function buildCustomEditFields($object)
    {
        $fields = array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(pht('Name'))
                ->setDescription(pht('Name of the panel.'))
                ->setConduitDescription(pht('Rename the panel.'))
                ->setConduitTypeDescription(pht('New panel name.'))
                ->setTransactionType(
                    PhabricatorDashboardPanelNameTransaction::TRANSACTIONTYPE)
                ->setIsRequired(true)
                ->setValue($object->getName()),
        );

        $panel_fields = $object->getEditEngineFields();
        foreach ($panel_fields as $panel_field) {
            $fields[] = $panel_field;
        }

        return $fields;
    }
}
