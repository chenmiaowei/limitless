<?php

namespace orangins\lib\view\layout;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\events\constant\PhabricatorEventType;
use orangins\lib\events\RenderActionListEvent;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use Yii;

/**
 * Class PhabricatorActionListView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class PhabricatorActionListView extends AphrontTagView
{

    /**
     * @var array
     */
    private $actions = array();
    /**
     * @var
     */
    private $object;

    /**
     * @param ActiveRecordPHID $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject(ActiveRecordPHID $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @param PhabricatorActionView $view
     * @return $this
     * @author 陈妙威
     */
    public function addAction(PhabricatorActionView $view)
    {
        $this->actions[] = $view;
        return $this;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        if (!$this->actions) {
            return null;
        }

        return 'ul';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'nav nav-sidebar';
        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array|null
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $viewer = $this->getViewer();

        $renderActionListEvent = new RenderActionListEvent();
        $renderActionListEvent
            ->setActions($this->actions)
            ->setObject($this->object)
            ->setUser($this->getViewer());
        Yii::$app->trigger(PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS, $renderActionListEvent);
        $actions = $renderActionListEvent->getActions();

        if (!$actions) {
            return null;
        }

        foreach ($actions as $action) {
            $action->setViewer($viewer);
        }
        $items = array();
        foreach ($actions as $action) {
            foreach ($action->getItems() as $item) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDropdownMenuMetadata()
    {
        return array(
            'items' => (string)JavelinHtml::hsprintf('%s', $this),
        );
    }
}
