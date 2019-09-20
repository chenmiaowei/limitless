<?php

namespace orangins\modules\dashboard\paneltype;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIListView;
use orangins\modules\dashboard\assets\JavelinDashboardTabPanelBehaviorAsset;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use PhutilJSONParserException;
use PhutilURI;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardTabsPanelType
 * @package orangins\modules\dashboard\paneltype
 * @author 陈妙威
 */
final class PhabricatorDashboardTabsPanelType
    extends PhabricatorDashboardPanelType
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeKey()
    {
        return 'tabs';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeName()
    {
        return \Yii::t("app", 'Tab Panel');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-window-maximize';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeDescription()
    {
        return \Yii::t("app", 'Use tabs to switch between several other panels.');
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array|mixed
     * @author 陈妙威
     */
    protected function newEditEngineFields(PhabricatorDashboardPanel $panel)
    {
        return array();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRenderAsync()
    {
        // The actual tab panel itself is cheap to render.
        return false;
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array|mixed
     * @author 陈妙威
     */
    public function getPanelConfiguration(PhabricatorDashboardPanel $panel)
    {
        $config = $panel->getProperty('config');

        if (!is_array($config)) {
            // NOTE: The older version of this panel stored raw JSON.
            try {
                $config = phutil_json_decode($config);
            } catch (PhutilJSONParserException $ex) {
                $config = array();
            }
        }

        return $config;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderPanelContent(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine)
    {

        $is_edit = $engine->isEditMode();
        $config = $this->getPanelConfiguration($panel);

        $context_object = $engine->getContextObject();
        if (!$context_object) {
            $context_object = $panel;
        }

        $context_phid = $context_object->getPHID();

        $list = (new PHUIListView())
            ->setType(PHUIListView::NAVBAR_LIST);

        $ids = ipull($config, 'panelID');
        if ($ids) {
            $panels = PhabricatorDashboardPanel::find()
                ->setViewer($viewer)
                ->withIDs($ids)
                ->execute();
        } else {
            $panels = array();
        }

        $id = $panel->getID();

        $add_uri = new PhutilURI(Url::to([
            '/dashboard/panel/tabs',
            'id' => $id,
            'op' => 'add',
            'contextPHID' => $context_phid
        ]));

        $remove_uri = new PhutilURI(Url::to([
            '/dashboard/panel/tabs',
            'id' => $id,
            'op' => 'remove',
            'contextPHID' => $context_phid
        ]));

        $rename_uri = new PhutilURI(Url::to([
            '/dashboard/panel/tabs',
            'id' => $id,
            'op' => 'rename',
            'contextPHID' => $context_phid
        ]));

        $selected = 0;

        $last_idx = null;
        foreach ($config as $idx => $tab_spec) {
            $panel_id = ArrayHelper::getValue($tab_spec, 'panelID');
            $subpanel = ArrayHelper::getValue($panels, $panel_id);

            $name = ArrayHelper::getValue($tab_spec, 'name');
            if (!strlen($name)) {
                if ($subpanel) {
                    $name = $subpanel->getName();
                }
            }

            if (!strlen($name)) {
                $name = pht('Unnamed Tab');
            }

            $tab_view = (new PHUIListItemView())
                ->setHref('#')
                ->setSelected((string)$idx === (string)$selected)
                ->addSigil('dashboard-tab-panel-tab')
                ->setMetadata(array('panelKey' => $idx))
                ->setName($name);

            if ($is_edit) {
                $dropdown_menu = (new PhabricatorActionListView())
                    ->setViewer($viewer);

                $remove_uri1 = clone $remove_uri;
                $remove_tab_uri = $remove_uri1
                    ->replaceQueryParam('target', $idx);

                $rename_uri1 = clone $rename_uri;
                $rename_tab_uri = $rename_uri1
                    ->replaceQueryParam('target', $idx);

                if ($subpanel) {
                    $details_uri = $subpanel->getURI();
                } else {
                    $details_uri = null;
                }

                $edit_uri = Url::to([
                    '/dashboard/panel/edit',
                    'id' => $panel_id
                ]);
                if ($subpanel) {
                    $can_edit = PhabricatorPolicyFilter::hasCapability(
                        $viewer,
                        $subpanel,
                        PhabricatorPolicyCapability::CAN_EDIT);
                } else {
                    $can_edit = false;
                }

                $dropdown_menu->addAction(
                    (new PhabricatorActionView())
                        ->setName(pht('Rename Tab'))
                        ->setIcon('fa-pencil')
                        ->setHref($rename_tab_uri)
                        ->setWorkflow(true));

                $dropdown_menu->addAction(
                    (new PhabricatorActionView())
                        ->setName(pht('Remove Tab'))
                        ->setIcon('fa-times')
                        ->setHref($remove_tab_uri)
                        ->setWorkflow(true));

                $dropdown_menu->addAction(
                    (new PhabricatorActionView())
                        ->setType(PhabricatorActionView::TYPE_DIVIDER));

                $dropdown_menu->addAction(
                    (new PhabricatorActionView())
                        ->setName(pht('Edit Panel'))
                        ->setIcon('fa-pencil')
                        ->setHref($edit_uri)
                        ->setWorkflow(true)
                        ->setDisabled(!$can_edit));

                $dropdown_menu->addAction(
                    (new PhabricatorActionView())
                        ->setName(pht('View Panel Details'))
                        ->setIcon('fa-windows')
                        ->setHref($details_uri)
                        ->setDisabled(!$subpanel));

                $tab_view
                    ->setActionIcon('fa-caret-down', '#')
                    ->setDropdownMenu($dropdown_menu);
            }

            $list->addMenuItem($tab_view);

            $last_idx = $idx;
        }

        if ($is_edit) {
            $actions = (new PhabricatorActionListView())
                ->setViewer($viewer);

            $add_last_uri = clone $add_uri;
            if ($last_idx) {
                $add_last_uri->replaceQueryParam('after', $last_idx);
            }

            $actions->addAction(
                (new PhabricatorActionView())
                    ->setName(pht('Add Existing Panel'))
                    ->setIcon('fa-window-maximize')
                    ->setHref($add_last_uri)
                    ->setWorkflow(true));

            $list->addMenuItem(
                (new PHUIListItemView())
                    ->setHref('#')
                    ->setDisabled(true)
                    ->setSelected(false)
                    ->setName(pht("\xC2\xB7 \xC2\xB7 \xC2\xB7"))
                    ->setActionIcon('fa-caret-down', '#')
                    ->setDropdownMenu($actions));
        }

        $parent_phids = $engine->getParentPanelPHIDs();
        $parent_phids[] = $panel->getPHID();

        // TODO: Currently, we'll load all the panels on page load. It would be
        // vaguely nice to load hidden panels only when the user selects them.

        // TODO: Maybe we should persist which panel the user selected, so it
        // remains selected across page loads.

        $content = array();
        $panel_list = array();
        $no_headers = PhabricatorDashboardPanelRenderingEngine::HEADER_MODE_NONE;
        foreach ($config as $idx => $tab_spec) {
            $panel_id = ArrayHelper::getValue($tab_spec, 'panelID');
            $subpanel = ArrayHelper::getValue($panels, $panel_id);

            if ($subpanel) {
                $panel_content = (new PhabricatorDashboardPanelRenderingEngine())
                    ->setViewer($viewer)
                    ->setEnableAsyncRendering(true)
                    ->setContextObject($context_object)
                    ->setParentPanelPHIDs($parent_phids)
                    ->setPanel($subpanel)
                    ->setPanelPHID($subpanel->getPHID())
                    ->setHeaderMode($no_headers)
                    ->setMovable(false)
                    ->renderPanel();
            } else {
                $panel_content = pht('(Invalid Panel)');
            }

            $content_id = JavelinHtml::generateUniqueNodeId();

            $content[] = phutil_tag(
                'div',
                array(
                    'id' => $content_id,
                    'style' => ($idx == $selected) ? null : 'display: none',
                ),
                $panel_content);

            $panel_list[] = array(
                'panelKey' => (string)$idx,
                'panelContentID' => $content_id,
            );
        }

        if (!$content) {
            if ($is_edit) {
                $message = pht(
                    'This tab panel does not have any tabs yet. Use "Add Tab" to ' .
                    'create or place a tab.');
            } else {
                $message = pht(
                    'This tab panel does not have any tabs yet.');
            }

            $content = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NODATA)
                ->setErrors(
                    array(
                        $message,
                    ));

            $content = (new PHUIBoxView())
                ->addClass('mlt mlb')
                ->appendChild($content);
        }

        JavelinHtml::initBehavior(new JavelinDashboardTabPanelBehaviorAsset());

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'sigil' => 'dashboard-tab-panel-container',
                'meta' => array(
                    'panels' => $panel_list,
                ),
            ),
            array(
                $list,
                $content,
            ));
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getSubpanelPHIDs(PhabricatorDashboardPanel $panel)
    {
        $config = $this->getPanelConfiguration($panel);

        $panel_ids = array();
        foreach ($config as $tab_key => $tab_spec) {
            $panel_ids[] = $tab_spec['panelID'];
        }

        if ($panel_ids) {
            $panels = PhabricatorDashboardPanel::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withIDs($panel_ids)
                ->execute();
        } else {
            $panels = array();
        }

        return mpull($panels, 'getPHID');
    }
}
