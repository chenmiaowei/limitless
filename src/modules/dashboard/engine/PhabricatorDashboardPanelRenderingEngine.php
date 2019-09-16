<?php

namespace orangins\modules\dashboard\engine;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\OranginsObject;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\modules\dashboard\assets\JavelinDashboardAsyncPanelBehaviorAsset;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use PhutilInvalidStateException;
use PhutilNumber;
use PhutilURI;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardPanelRenderingEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelRenderingEngine extends OranginsObject
{

    /**
     *
     */
    const HEADER_MODE_NORMAL = 'normal';
    /**
     *
     */
    const HEADER_MODE_NONE = 'none';
    /**
     *
     */
    const HEADER_MODE_EDIT = 'edit';

    /**
     * @var PhabricatorDashboardPanel
     */
    private $panel;
    /**
     * @var
     */
    private $panelPHID;
    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var
     */
    private $enableAsyncRendering;
    /**
     * @var
     */
    private $parentPanelPHIDs;
    /**
     * @var string
     */
    private $headerMode = self::HEADER_MODE_NORMAL;
    /**
     * @var bool
     */
    private $movable = true;
    /**
     * @var
     */
    private $panelHandle;
    /**
     * @var
     */
    private $editMode;
    /**
     * @var
     */
    private $contextObject;
    /**
     * @var
     */
    private $panelKey;

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setContextObject($object)
    {
        $this->contextObject = $object;
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
     * @param $panel_key
     * @return $this
     * @author 陈妙威
     */
    public function setPanelKey($panel_key)
    {
        $this->panelKey = $panel_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return $this->panelKey;
    }

    /**
     * @param $header_mode
     * @return $this
     * @author 陈妙威
     */
    public function setHeaderMode($header_mode)
    {
        $this->headerMode = $header_mode;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHeaderMode()
    {
        return $this->headerMode;
    }

    /**
     * @param PhabricatorObjectHandle $panel_handle
     * @return $this
     * @author 陈妙威
     */
    public function setPanelHandle(PhabricatorObjectHandle $panel_handle)
    {
        $this->panelHandle = $panel_handle;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanelHandle()
    {
        return $this->panelHandle;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function isEditMode()
    {
        return $this->editMode;
    }

    /**
     * @param $mode
     * @return $this
     * @author 陈妙威
     */
    public function setEditMode($mode)
    {
        $this->editMode = $mode;
        return $this;
    }

    /**
     * Allow the engine to render the panel via Ajax.
     */
    public function setEnableAsyncRendering($enable)
    {
        $this->enableAsyncRendering = $enable;
        return $this;
    }

    /**
     * @param array $parents
     * @return $this
     * @author 陈妙威
     */
    public function setParentPanelPHIDs(array $parents)
    {
        $this->parentPanelPHIDs = $parents;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getParentPanelPHIDs()
    {
        return $this->parentPanelPHIDs;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return $this
     * @author 陈妙威
     */
    public function setPanel(PhabricatorDashboardPanel $panel)
    {
        $this->panel = $panel;
        return $this;
    }

    /**
     * @param $movable
     * @return $this
     * @author 陈妙威
     */
    public function setMovable($movable)
    {
        $this->movable = $movable;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getMovable()
    {
        return $this->movable;
    }

    /**
     * @return PhabricatorDashboardPanel
     * @author 陈妙威
     */
    public function getPanel()
    {
        return $this->panel;
    }

    /**
     * @param $panel_phid
     * @return $this
     * @author 陈妙威
     */
    public function setPanelPHID($panel_phid)
    {
        $this->panelPHID = $panel_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanelPHID()
    {
        return $this->panelPHID;
    }

    /**
     * @return PHUIObjectBoxView
     * @throws Exception
     * @author 陈妙威
     */
    public function renderPanel()
    {
        $panel = $this->getPanel();

        if (!$panel) {
            $handle = $this->getPanelHandle();
            if ($handle->getPolicyFiltered()) {
                return $this->renderErrorPanel(
                    pht('Restricted Panel'),
                    pht(
                        'You do not have permission to see this panel.'));
            } else {
                return $this->renderErrorPanel(
                    pht('Invalid Panel'),
                    pht(
                        'This panel is invalid or does not exist. It may have been ' .
                        'deleted.'));
            }
        }

        $panel_type = $panel->getImplementation();
        if (!$panel_type) {
            return $this->renderErrorPanel(
                $panel->getName(),
                pht(
                    'This panel has type "%s", but that panel type is not known to ' .
                    'Phabricator.',
                    $panel->getPanelType()));
        }

        try {
            $this->detectRenderingCycle($panel);

            if ($this->enableAsyncRendering) {
                if ($panel_type->shouldRenderAsync()) {
                    return $this->renderAsyncPanel();
                }
            }

            return $this->renderNormalPanel();
        } catch (Exception $ex) {
            return $this->renderErrorPanel(
                $panel->getName(),
                pht(
                    '%s: %s',
                    phutil_tag('strong', array(), get_class($ex)),
                    $ex->getMessage()));
        }
    }

    /**
     * @return PHUIObjectBoxView
     * @author 陈妙威
     * @throws Exception
     */
    private function renderNormalPanel()
    {
        $panel = $this->getPanel();
        $panel_type = $panel->getImplementation();

        $content = $panel_type->renderPanelContent(
            $this->getViewer(),
            $panel,
            $this);
        $header = $this->renderPanelHeader();

        return $this->renderPanelDiv(
            $content,
            $header);
    }


    /**
     * @return PHUIObjectBoxView
     * @author 陈妙威
     * @throws Exception
     */
    private function renderAsyncPanel()
    {
        $context_phid = $this->getContextPHID();
        $panel = $this->getPanel();

        $panel_id = JavelinHtml::generateUniqueNodeId();

        JavelinHtml::initBehavior(
            new JavelinDashboardAsyncPanelBehaviorAsset(),
            array(
                'panelID' => $panel_id,
                'parentPanelPHIDs' => $this->getParentPanelPHIDs(),
                'headerMode' => $this->getHeaderMode(),
                'contextPHID' => $context_phid,
                'panelKey' => $this->getPanelKey(),
                'uri' => Url::to([
                    '/dashboard/panel/render',
                    'id' =>  $panel->getID(),
                ]),
            ));

        $header = $this->renderPanelHeader();
        $content = (new PHUIPropertyListView())
            ->addTextContent(pht('Loading...'));

        return $this->renderPanelDiv(
            $content,
            $header,
            $panel_id);
    }

    /**
     * @param $title
     * @param $body
     * @return PHUIObjectBoxView
     * @throws Exception
     * @author 陈妙威
     */
    private function renderErrorPanel($title, $body)
    {
        switch ($this->getHeaderMode()) {
            case self::HEADER_MODE_NONE:
                $header = null;
                break;
            case self::HEADER_MODE_EDIT:
                $header = (new PHUIHeaderView())
                    ->setHeader($title);
                $header = $this->addPanelHeaderActions($header);
                break;
            case self::HEADER_MODE_NORMAL:
            default:
                $header = (new PHUIHeaderView())
                    ->setHeader($title);
                break;
        }

        $icon = (new PHUIIconView())
            ->setIcon('fa-warning red msr');

        $content = (new PHUIBoxView())
            ->addClass('dashboard-box')
            ->addMargin(PHUI::MARGIN_LARGE)
            ->appendChild($icon)
            ->appendChild($body);

        return $this->renderPanelDiv(
            $content,
            $header);
    }

    /**
     * @param $content
     * @param null $header
     * @param null $id
     * @return PHUIObjectBoxView
     * @throws Exception
     * @author 陈妙威
     */
    private function renderPanelDiv(
        $content,
        $header = null,
        $id = null)
    {
//        require_celerity_resource('phabricator-dashboard-css');

        $panel = $this->getPanel();
        $panel_type = $panel->getImplementation();
        if (!$id) {
            $id = JavelinHtml::generateUniqueNodeId();
        }

        $box = new PHUIObjectBoxView();

        if ($content instanceof PhabricatorApplicationSearchResultView) {
            if ($content->getObjectList()) {
                $box->setObjectList($content->getObjectList());
            }
            if ($content->getTable()) {
                $box->setTable($content->getTable());
            }
            if ($content->getContent()) {
                $box->appendChild($content->getContent());
            }
        } else {
            $box->appendChild($content);
        }

        $classes = ['dashboard-box'];
        $classes = ArrayHelper::merge($classes, $panel_type->getCardClasses($panel));
        $box
            ->setHeader($header)
            ->setID($id)
            ->addClass(implode(" ", $classes))
            ->addSigil('dashboard-panel');

        if ($this->getMovable()) {
            $box->addSigil('panel-movable');
        }

        if ($panel) {
            $box->setMetadata(
                array(
                    'panelKey' => $this->getPanelKey(),
                ));
        }

        return $box;
    }


    /**
     * @return null|PHUIHeaderView
     * @author 陈妙威
     * @throws Exception
     */
    private function renderPanelHeader()
    {

        $panel = $this->getPanel();
        switch ($this->getHeaderMode()) {
            case self::HEADER_MODE_NONE:
                $header = null;
                break;
            case self::HEADER_MODE_EDIT:
                // In edit mode, include the panel monogram to make managing boards
                // a little easier.
                $header_text = pht('%s %s', $panel->getMonogram(), $panel->getName());
                $header = (new PHUIHeaderView())
                    ->setHeader($header_text);
                $header = $this->addPanelHeaderActions($header);
                break;
            case self::HEADER_MODE_NORMAL:
            default:
                $header = (new PHUIHeaderView())
                    ->setHeader($panel->getName());
                $panel_type = $panel->getImplementation();
                $header = $panel_type->adjustPanelHeader(
                    $this->getViewer(),
                    $panel,
                    $this,
                    $header);
                break;
        }
        return $header;
    }

    /**
     * @param PHUIHeaderView $header
     * @return PHUIHeaderView
     * @throws Exception
     * @author 陈妙威
     */
    private function addPanelHeaderActions(
        PHUIHeaderView $header)
    {

        $viewer = $this->getViewer();
        $panel = $this->getPanel();
        $context_phid = $this->getContextPHID();

        $actions = array();

        if ($panel) {
            $panel_id = $panel->getID();

            $edit_uri = Url::to([
                '/dashboard/panel/edit',
                'id' => $panel_id
            ]);
            $params = array(
                'contextPHID' => $context_phid,
            );
            $edit_uri = new PhutilURI($edit_uri, $params);

            $actions[] = (new PhabricatorActionView())
                ->setIcon('fa-pencil')
                ->setName(pht('Edit Panel'))
                ->setHref($edit_uri);

            $actions[] = (new PhabricatorActionView())
                ->setIcon('fa-windows')
                ->setName(pht('View Panel Details'))
                ->setHref($panel->getURI());
        }

        if ($context_phid) {
            $panel_phid = $this->getPanelPHID();

            $remove_uri = Url::to([
                '/dashboard/index/adjust',
                'op' => 'remove',
                'contextPHID' => $context_phid,
                'panelKey' => $this->getPanelKey(),
            ]);
            $actions[] = (new PhabricatorActionView())
                ->setIcon('fa-times')
                ->setHref($remove_uri)
                ->setName(pht('Remove Panel'))
                ->setWorkflow(true);
        }

        $dropdown_menu = (new PhabricatorActionListView())
            ->setViewer($viewer);

        foreach ($actions as $action) {
            $dropdown_menu->addAction($action);
        }

        $action_menu = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-cog')
            ->addClass('btn-xs')
            ->setColor(PhabricatorEnv::getEnvConfig('ui.widget-color'))
            ->setText(pht('Manage Panel'))
            ->setDropdownMenu($dropdown_menu);

        $header->addActionLink($action_menu);

        return $header;
    }


    /**
     * Detect graph cycles in panels, and deeply nested panels.
     *
     * This method throws if the current rendering stack is too deep or contains
     * a cycle. This can happen if you embed layout panels inside each other,
     * build a big stack of panels, or embed a panel in remarkup inside another
     * panel. Generally, all of this stuff is ridiculous and we just want to
     * shut it down.
     *
     * @param PhabricatorDashboardPanel Panel being rendered.
     * @return void
     * @throws PhutilInvalidStateException
     * @throws Exception
     */
    private function detectRenderingCycle(PhabricatorDashboardPanel $panel)
    {
        if ($this->parentPanelPHIDs === null) {
            throw new PhutilInvalidStateException('setParentPanelPHIDs');
        }

        $max_depth = 4;
        if (count($this->parentPanelPHIDs) >= $max_depth) {
            throw new Exception(
                pht(
                    'To render more than %s levels of panels nested inside other ' .
                    'panels, purchase a subscription to Phabricator Gold.',
                    new PhutilNumber($max_depth)));
        }

        if (in_array($panel->getPHID(), $this->parentPanelPHIDs)) {
            throw new Exception(
                pht(
                    'You awake in a twisting maze of mirrors, all alike. ' .
                    'You are likely to be eaten by a graph cycle. ' .
                    'Should you escape alive, you resolve to be more careful about ' .
                    'putting dashboard panels inside themselves.'));
        }
    }

    /**
     * @return null
     * @author 陈妙威
     */
    private function getContextPHID()
    {
        $context = $this->getContextObject();

        if ($context) {
            return $context->getPHID();
        }

        return null;
    }
}
