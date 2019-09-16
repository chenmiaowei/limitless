<?php

namespace orangins\modules\search\menuitems;

use orangins\modules\dashboard\engine\PhabricatorDashboardRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\typeahead\PhabricatorDashboardDatasource;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorDatasourceEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * 主菜单添加仪表盘
 * Class PhabricatorDashboardProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorDashboardProfileMenuItem extends PhabricatorProfileMenuItem
{
    /**
     *
     */
    const MENUITEMKEY = 'dashboard';

    /**
     *
     */
    const FIELD_DASHBOARD = 'dashboardPHID';
    /**
     * @var PhabricatorObjectHandle
     */
    public $dashboardHandle;

    /**
     * @var PhabricatorDashboard
     */
    private $dashboard;

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMenuItemTypeIcon()
    {
        return 'fa-dashboard';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return Yii::t("app", 'Dashboard');
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canAddToObject($object)
    {
        return true;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canMakeDefault(PhabricatorProfileMenuItemConfiguration $config)
    {
        return true;
    }

    /**
     * @param $dashboard
     * @return $this
     * @author 陈妙威
     */
    public function attachDashboard($dashboard)
    {
        $this->dashboard = $dashboard;
        return $this;
    }

    /**
     * @return PhabricatorDashboard
     * @author 陈妙威
     */
    public function getDashboard()
    {
        $dashboard = $this->dashboard;
        if (!$dashboard) {
            return null;
        } else if ($dashboard->isArchived()) {
            return null;
        }
        return $dashboard;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return null
     * @throws \Exception
     * @author 陈妙威
     */
    public function newPageContent(PhabricatorProfileMenuItemConfiguration $config)
    {
        $viewer = $this->getViewer();

        $dashboard_phid = $config->getMenuItemProperty('dashboardPHID');

        // Reload the dashboard to attach panels, which we need for rendering.
        $dashboard = PhabricatorDashboard::find()
            ->setViewer($viewer)
            ->withPHIDs(array($dashboard_phid))
            ->needPanels(true)
            ->executeOne();
        if (!$dashboard) {
            return null;
        }

        $engine = (new PhabricatorDashboardRenderingEngine())
            ->setViewer($viewer)
            ->setDashboard($dashboard);

        return $engine->renderDashboard();
    }

    /**
     * @param array $items
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function willGetMenuItemViewList(array $items)
    {
        $viewer = $this->getViewer();
        $dashboard_phids = array();
        foreach ($items as $item) {
            $dashboard_phids[] = $item->getMenuItemProperty('dashboardPHID');
        }

        $dashboards = PhabricatorDashboard::find()
            ->setViewer($viewer)
            ->withPHIDs($dashboard_phids)
            ->execute();

        $handles = $viewer->loadHandles($dashboard_phids);

        $dashboards = mpull($dashboards, null, 'getPHID');
        foreach ($items as $item) {
            $dashboard_phid = $item->getMenuItemProperty('dashboardPHID');
            $dashboard = ArrayHelper::getValue($dashboards, $dashboard_phid, null);
            /** @var PhabricatorDashboardProfileMenuItem $menuItem */
            $menuItem = $item->getMenuItem();
            $menuItem
                ->attachDashboard($dashboard)
                ->setDashboardHandle($handles[$dashboard_phid]);
        }
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return string
     * @author 陈妙威
     */
    public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config)
    {
        $dashboard = $this->getDashboard();

        if (!$dashboard) {
            return Yii::t("app", '(Restricted/Invalid Dashboard)');
        }

        if (strlen($this->getName($config))) {
            return $this->getName($config);
        } else {
            return $dashboard->getName();
        }
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威
     */
    public function buildEditEngineFields(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return array(
            (new PhabricatorDatasourceEditField())
                ->setKey(self::FIELD_DASHBOARD)
                ->setLabel(Yii::t("app", 'Dashboard'))
                ->setIsRequired(true)
                ->setDatasource(new PhabricatorDashboardDatasource())
                ->setSingleValue($config->getMenuItemProperty('dashboardPHID')),
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(Yii::t("app", 'Name'))
                ->setValue($this->getName($config)),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed
     * @author 陈妙威
     */
    private function getName(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return $config->getMenuItemProperty('name');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威
     */
    protected function newMenuItemViewList(
        PhabricatorProfileMenuItemConfiguration $config)
    {

        $is_disabled = true;
        $action_uri = null;

        $dashboard = $this->getDashboard();
        if ($dashboard) {
            if ($dashboard->isArchived()) {
                $icon = 'fa-ban';
                $name = $this->getDisplayName($config);
            } else {
                $icon = $dashboard->getIcon();
                $name = $this->getDisplayName($config);
                $is_disabled = false;
                $action_uri = $dashboard->getURI();
            }
        } else {
            $icon = 'fa-ban';
            if ($this->getDashboardHandle()->getPolicyFiltered()) {
                $name = pht('Restricted Dashboard');
            } else {
                $name = pht('Invalid Dashboard');
            }
        }

        $uri = $this->getItemViewURI($config);

        $item = $this->newItemView()
            ->setURI($uri)
            ->setName($name)
            ->setIcon($icon)
            ->setDisabled($is_disabled);

        if ($action_uri) {
            $item->newAction($action_uri);
        }

        return array(
            $item,
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @param $field_key
     * @param $value
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function validateTransactions(
        PhabricatorProfileMenuItemConfiguration $config,
        $field_key,
        $value,
        array $xactions)
    {

        $viewer = $this->getViewer();
        $errors = array();

        if ($field_key == self::FIELD_DASHBOARD) {
            if ($this->isEmptyTransaction($value, $xactions)) {
                $errors[] = $this->newRequiredError(
                    Yii::t("app", 'You must choose a dashboard.'),
                    $field_key);
            }

            foreach ($xactions as $xaction) {
                $new = $xaction['new'];

                if (!$new) {
                    continue;
                }

                if ($new === $value) {
                    continue;
                }

                $dashboards = PhabricatorDashboard::find()
                    ->setViewer($viewer)
                    ->withPHIDs(array($new))
                    ->execute();
                if (!$dashboards) {
                    $errors[] = $this->newInvalidError(
                        Yii::t("app",
                            'Dashboard "%s" is not a valid dashboard which you have ' .
                            'permission to see.',
                            $new),
                        $xaction['xaction']);
                }
            }
        }

        return $errors;
    }

    /**
     * @return PhabricatorObjectHandle
     * @author 陈妙威
     */
    private function getDashboardHandle()
    {
        return $this->dashboardHandle;
    }

    /**
     * @param PhabricatorObjectHandle $handle
     * @return $this
     * @author 陈妙威
     */
    private function setDashboardHandle(PhabricatorObjectHandle $handle)
    {
        $this->dashboardHandle = $handle;
        return $this;
    }

}
