<?php

namespace orangins\lib\view\phui;

use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontTagView;
use Exception;
use Yii;

/**
 * Class PHUIListView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIListView extends AphrontTagView
{

    /**
     *
     */
    const NAVBAR_LIST = 'phui-list-navbar';
    /**
     *
     */
    const SIDENAV_LIST = 'phui-list-sidenav';
    /**
     *
     */
    const TABBAR_LIST = 'phui-list-tabbar';

    /**
     *
     */
    const TYPE_SIDEBAR = "nav-sidebar";
    /**
     *
     */
    const TYPE_TABS = "nav-tabs";

    /**
     * @var PHUIListItemView[]
     */
    private $items = array();
    /**
     * @var
     */
    private $type = self::TYPE_SIDEBAR;

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }

    /**
     * @param $name
     * @param null $key
     * @return PHUIListItemView
     * @author 陈妙威
     * @throws Exception
     */
    public function newLabel($name, $key = null)
    {
        $item = (new PHUIListItemView())
            ->setType(PHUIListItemView::TYPE_LABEL)
            ->setName($name);

        if ($key !== null) {
            $item->setKey($key);
        }

        $this->addMenuItem($item);

        return $item;
    }

    /**
     * @param $name
     * @param $href
     * @param null $key
     * @return PHUIListItemView
     * @author 陈妙威
     * @throws Exception
     */
    public function newLink($name, $href, $key = null)
    {
        $item = (new PHUIListItemView())
            ->setType(PHUIListItemView::TYPE_LINK)
            ->setName($name)
            ->setHref($href);

        if ($key !== null) {
            $item->setKey($key);
        }

        $this->addMenuItem($item);

        return $item;
    }

    /**
     * @param $name
     * @param $href
     * @return PHUIListItemView
     * @author 陈妙威
     * @throws Exception
     */
    public function newButton($name, $href)
    {
        $item = (new PHUIListItemView())
            ->setType(PHUIListItemView::TYPE_BUTTON)
            ->setName($name)
            ->setHref($href);

        $this->addMenuItem($item);

        return $item;
    }

    /**
     * @param PHUIListItemView $item
     * @return PHUIListView
     * @throws Exception
     * @author 陈妙威
     */
    public function addMenuItem(PHUIListItemView $item)
    {
        return $this->addMenuItemAfter(null, $item);
    }

    /**
     * @param $key
     * @param PHUIListItemView $item
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function addMenuItemAfter($key, PHUIListItemView $item)
    {
        if ($key === null) {
            $this->items[] = $item;
            return $this;
        }

        if (!$this->getItem($key)) {
            throw new Exception(Yii::t("app", "No such key '{0}' to add menu item after!", [
                $key
            ]));
        }

        $result = array();
        foreach ($this->items as $other) {
            $result[] = $other;
            if ($other->getKey() == $key) {
                $result[] = $item;
            }
        }

        $this->items = $result;
        return $this;
    }

    /**
     * @param $key
     * @param PHUIListItemView $item
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function addMenuItemBefore($key, PHUIListItemView $item)
    {
        if ($key === null) {
            array_unshift($this->items, $item);
            return $this;
        }

        $this->requireKey($key);

        $result = array();
        foreach ($this->items as $other) {
            if ($other->getKey() == $key) {
                $result[] = $item;
            }
            $result[] = $other;
        }

        $this->items = $result;
        return $this;
    }

    /**
     * @param $key
     * @param PHUIListItemView $item
     * @return PHUIListView
     * @throws Exception
     * @author 陈妙威
     */
    public function addMenuItemToLabel($key, PHUIListItemView $item)
    {
        $this->requireKey($key);

        $other = $this->getItem($key);
        if ($other->getType() != PHUIListItemView::TYPE_LABEL) {
            throw new Exception(Yii::t("app", "Menu item '{0}' is not a label!", [
                $key
            ]));
        }

        $seen = false;
        $after = null;
        foreach ($this->items as $other) {
            if (!$seen) {
                if ($other->getKey() == $key) {
                    $seen = true;
                }
            } else {
                if ($other->getType() == PHUIListItemView::TYPE_LABEL) {
                    break;
                }
            }
            $after = $other->getKey();
        }

        return $this->addMenuItemAfter($after, $item);
    }

    /**
     * @param $key
     * @throws Exception
     * @author 陈妙威
     */
    private function requireKey($key)
    {
        if (!$this->getItem($key)) {
            throw new Exception(Yii::t("app", "No menu item with key '{0}' exists!", [
                $key
            ]));
        }
    }

    /**
     * @param $key
     * @return PHUIListItemView
     * @author 陈妙威
     */
    public function getItem($key)
    {
        $key = (string)$key;

        // NOTE: We could optimize this, but need to update any map when items have
        // their keys change. Since that's moderately complex, wait for a profile
        // or use case.

        foreach ($this->items as $item) {
            if ($item->getKey() == $key) {
                return $item;
            }

            if(is_array($item->getSubListItems()) && count($item->getSubListItems())) {
                foreach ($item->getSubListItems() as $subListItem) {
                    if ($subListItem->getKey() == $key) {
                        return $item;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @return PHUIListItemView[]
     * @author 陈妙威
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @throws Exception
     * @author 陈妙威
     */
    public function willRender()
    {
        $key_map = array();
        foreach ($this->items as $item) {
            $key = $item->getKey();
            if ($key !== null) {
                if (isset($key_map[$key])) {
                    throw new Exception(
                        Yii::t("app", "Menu contains duplicate items with key '{0}'!", [
                            $key
                        ]));
                }
                $key_map[$key] = $item;
            }
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'ul';
    }

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = [];
        $classes[] = 'nav';
        if ($this->type) {
            $classes[] = $this->type;
        }
        return array(
            'class' => implode(' ', $classes),
            'data-nav-type' => 'accordion'
        );
    }

    /**
     * @return array|AphrontResponse|string
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        return $this->items;
    }
}
