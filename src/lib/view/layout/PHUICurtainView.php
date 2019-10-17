<?php

namespace orangins\lib\view\layout;

use orangins\lib\view\AphrontTagView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIObjectBoxView;

/**
 * Class PHUICurtainView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class PHUICurtainView extends AphrontTagView
{

    /**
     * @var
     */
    private $actionList;
    /**
     * @var array
     */
    private $panels = array();

    /**
     * @param PhabricatorActionView $action
     * @return $this
     * @author 陈妙威
     */
    public function addAction(PhabricatorActionView $action)
    {
        $this->getActionList()->addAction($action);
        return $this;
    }

    /**
     * @param PHUICurtainPanelView $curtain_panel
     * @return $this
     * @author 陈妙威
     */
    public function addPanel(PHUICurtainPanelView $curtain_panel)
    {
        $this->panels[] = $curtain_panel;
        return $this;
    }

    /**
     * @return PHUICurtainPanelView
     * @author 陈妙威
     */
    public function newPanel()
    {
        $panel = new PHUICurtainPanelView();
        $this->addPanel($panel);

        // By default, application panels go at the bottom of the curtain, below
        // extension panels.
        $panel->setOrder(100000);

        return $panel;
    }

    /**
     * @param PhabricatorActionListView $action_list
     * @return $this
     * @author 陈妙威
     */
    public function setActionList(PhabricatorActionListView $action_list)
    {
        $this->actionList = $action_list;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getActionList()
    {
        return $this->actionList;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }

    /**
     * @return PHUIObjectBoxView
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function getTagContent()
    {
        $action_list = $this->actionList;

        $panels = $this->renderPanels();
        $box = (new PHUIObjectBoxView())
            ->appendChild($action_list)
            ->appendChild($panels)
            ->addBodyClass(PHUI::PADDING_NONE)
            ->addClass('phui-two-column-properties');

        // We want to hide this UI on mobile if there are no child panels
        if (!$panels) {
            $box->addClass('curtain-no-panels');
        }

        return $box;
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function renderPanels()
    {
        $panels = $this->panels;
        $panels =  msortv($panels, 'getOrderVector');

        return $panels;
    }
}
