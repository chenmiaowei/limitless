<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\modules\widgets\javelin\JavelinPHUIDropdownBehaviorAsset;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use PhutilSafeHTML;
use yii\helpers\ArrayHelper;

/**
 * Class PHUIListItemView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIListItemView extends AphrontTagView
{

    /**
     *
     */
    const TYPE_LINK = 'type-link';
    /**
     *
     */
    const TYPE_SPACER = 'type-spacer';
    /**
     *
     */
    const TYPE_LABEL = 'type-label';
    /**
     *
     */
    const TYPE_BUTTON = 'type-button';
    /**
     *
     */
    const TYPE_CUSTOM = 'type-custom';
    /**
     *
     */
    const TYPE_DIVIDER = 'type-divider';
    /**
     *
     */
    const TYPE_ICON = 'type-icon';

    /**
     *
     */
    const STATUS_WARN = 'phui-list-item-warn';
    /**
     *
     */
    const STATUS_FAIL = 'phui-list-item-fail';

    /**
     * @var bool
     */
    public $isNav = true;

    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $iconClass;
    /**
     * @var
     */
    private $href;
    /**
     * @var string
     */
    private $type = self::TYPE_LINK;
    /**
     * @var bool
     */
    private $isExternal;
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $icon;
    /**
     * @var
     */
    private $selected;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $renderNameAsTooltip;
    /**
     * @var
     */
    private $statusColor;
    /**
     * @var
     */
    private $order;
    /**
     * @var
     */
    private $aural;
    /**
     * @var
     */
    private $profileImage;
    /**
     * @var
     */
    private $indented;
    /**
     * @var
     */
    private $hideInApplicationMenu;
    /**
     * @var array
     */
    private $icons = array();
    /**
     * @var bool
     */
    private $openInNewWindow = false;
    /**
     * @var
     */
    private $tooltip;
    /**
     * @var
     */
    private $actionIcon;
    /**
     * @var
     */
    private $actionIconHref;
    /**
     * @var
     */
    private $count;
    /**
     * @var
     */
    private $rel;

    /**
     * @var bool
     */
    private $paddingNone = false;

    /**
     * @var array
     */
    private $linkClass = [];

    /**
     * @var array
     */
    private $linkTagAttributes = [];


    /**
     * @var PHUIListItemView[]
     */
    private $subListItems = [];

    /**
     * @param PHUIListItemView[] $subListItems
     * @return self
     */
    public function setSubListItems($subListItems)
    {
        $this->subListItems = $subListItems;
        return $this;
    }

    /**
     * @return PHUIListItemView[]
     */
    public function getSubListItems()
    {
        return $this->subListItems;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addLinkClass($class)
    {
        $this->linkClass[] = $class;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPaddingNone()
    {
        return $this->paddingNone;
    }

    /**
     * @param bool $paddingNone
     * @return self
     */
    public function setPaddingNone($paddingNone)
    {
        $this->paddingNone = $paddingNone;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNav()
    {
        return $this->isNav;
    }

    /**
     * @param $isNav
     * @return $this
     * @author 陈妙威
     */
    public function setIsNav($isNav)
    {
        $this->isNav = $isNav;
        return $this;
    }

    /**
     * @param $open_in_new_window
     * @return $this
     * @author 陈妙威
     */
    public function setOpenInNewWindow($open_in_new_window)
    {
        $this->openInNewWindow = $open_in_new_window;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getOpenInNewWindow()
    {
        return $this->openInNewWindow;
    }

    /**
     * @param $rel
     * @return $this
     * @author 陈妙威
     */
    public function setRel($rel)
    {
        $this->rel = $rel;
        return $this;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function setIconClass($class)
    {
        $this->iconClass = $class;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRel()
    {
        return $this->rel;
    }

    /**
     * @param $hide
     * @return $this
     * @author 陈妙威
     */
    public function setHideInApplicationMenu($hide)
    {
        $this->hideInApplicationMenu = $hide;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHideInApplicationMenu()
    {
        return $this->hideInApplicationMenu;
    }

    /**
     * @param PhabricatorActionListView $actions
     * @return $this
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function setDropdownMenu(PhabricatorActionListView $actions)
    {
        JavelinHtml::initBehavior(new JavelinPHUIDropdownBehaviorAsset());
        $this->addSigil('phui-dropdown-menu');
        $this->setMetadata($actions->getDropdownMenuMetadata());

        return $this;
    }

    /**
     * @param $aural
     * @return $this
     * @author 陈妙威
     */
    public function setAural($aural)
    {
        $this->aural = $aural;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAural()
    {
        return $this->aural;
    }

    /**
     * @param $order
     * @return $this
     * @author 陈妙威
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param $render_name_as_tooltip
     * @return $this
     * @author 陈妙威
     */
    public function setRenderNameAsTooltip($render_name_as_tooltip)
    {
        $this->renderNameAsTooltip = $render_name_as_tooltip;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRenderNameAsTooltip()
    {
        return $this->renderNameAsTooltip;
    }

    /**
     * @return array
     */
    public function getLinkTagAttributes()
    {
        return $this->linkTagAttributes;
    }

    /**
     * @param array $linkTagAttributes
     * @return self
     */
    public function setLinkTagAttributes($linkTagAttributes)
    {
        $this->linkTagAttributes = $linkTagAttributes;
        return $this;
    }

    /**
     * @param $selected
     * @return $this
     * @author 陈妙威
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @param $image
     * @return $this
     * @author 陈妙威
     */
    public function setProfileImage($image)
    {
        $this->profileImage = $image;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param $count
     * @return $this
     * @author 陈妙威
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * @param $indented
     * @return $this
     * @author 陈妙威
     */
    public function setIndented($indented)
    {
        $this->indented = $indented;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIndented()
    {
        return $this->indented;
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = (string)$key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
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
     * @return string
     * @author 陈妙威
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $icon
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setActionIcon($icon, $href)
    {
        $this->actionIcon = $icon;
        $this->actionIconHref = $href;
        return $this;
    }

    /**
     * @param $is_external
     * @return $this
     * @author 陈妙威
     */
    public function setIsExternal($is_external)
    {
        $this->isExternal = $is_external;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsExternal()
    {
        return $this->isExternal;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setStatusColor($color)
    {
        $this->statusColor = $color;
        return $this;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function addIcon($icon)
    {
        $this->icons[] = $icon;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getIcons()
    {
        return $this->icons;
    }

    /**
     * @param $tooltip
     * @return $this
     * @author 陈妙威
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'li';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array('position-relative');

        if ($this->isNav()) {
            $classes[] = 'nav-item';
        } else {
            $classes[] = 'list-inline-item';
        }

        if ($this->type === self::TYPE_LABEL) {
            $classes[] = 'nav-item-header';
        }

        if ($this->icon || $this->profileImage) {
            $classes[] = 'phui-list-item-has-icon';
        }


        if ($this->disabled) {
            $classes[] = 'phui-list-item-disabled';
        }

        if ($this->statusColor) {
            $classes[] = $this->statusColor;
        }

        if ($this->actionIcon) {
            $classes[] = 'phui-list-item-has-action-icon';
        }

        if (is_array($this->subListItems) && count($this->subListItems) > 0) {
            $classes[] = 'nav-item-submenu';
        }
        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $name = null;
        $icon = null;
        $meta = null;
        $sigil = null;

        if ($this->name) {
            if ($this->getRenderNameAsTooltip()) {
                JavelinHtml::initBehavior(new JavelinTooltipAsset());
                $sigil = 'has-tooltip';
                $meta = array(
                    'tip' => $this->name,
                    'align' => 'E',
                );
            } else {
                if ($this->tooltip) {
                    JavelinHtml::initBehavior(new JavelinTooltipAsset());
                    $sigil = 'has-tooltip';
                    $meta = array(
                        'tip' => $this->tooltip,
                        'align' => 'E',
                        'size' => 300,
                    );
                }

                $external = null;
                if ($this->isExternal) {
                    $external = " \xE2\x86\x97";
                }

                // If this element has an aural representation, make any name visual
                // only. This is primarily dealing with the links in the main menu like
                // "Profile" and "Logout". If we don't hide the name, the mobile
                // version of these elements will have two redundant names.

                $classes = array();
                $classes[] = 'phui-list-item-name';
                if ($this->aural !== null) {
                    $classes[] = 'visual-only';
                }

                $name = JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => implode(' ', $classes),
                    ),
                    array(
                        $this->name,
                        $external,
                    ));
            }
        }

        $aural = null;
        if ($this->aural !== null) {
            $aural = JavelinHtml::phutil_tag(
                'span',
                array(
                    'aural' => true,
                ),
                $this->aural);
        }

        if ($this->icon) {
            $icon_name = $this->icon;
            if ($this->getDisabled()) {
                $icon_name .= ' grey';
            }

            $icon = (new PHUIIconView())
                ->addClass($this->iconClass)
                ->setIcon($icon_name);
        }

        if ($this->profileImage) {
            $icon = (new PHUIIconView())
                ->setHeadSize(PHUIIconView::HEAD_SMALL)
                ->setImage($this->profileImage);
        }

        $classes = $this->linkClass;
        if ($this->href) {
            if ($this->isNav()) {
                $classes[] = 'nav-link';
                if ($this->selected) {
                    $classes[] = 'active';
                }
            }
        }

        if ($this->isPaddingNone()) {
            $classes[] = PHUI::PADDING_NONE;
        }

        if ($this->type === self::TYPE_LABEL) {
            $classes[] = 'text-uppercase font-size-xs line-height-xs';
        }

        if ($this->indented) {
            $classes[] = 'phui-list-item-indented';
        }



        $action_link = null;
        if ($this->actionIcon) {
            $action_icon = (new PHUIIconView())
                ->setIcon($this->actionIcon);
            $action_link = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => $this->actionIconHref,
                    'class' => 'position-absolute top-0 right-0 mt-2 mr-3 text-muted phui-list-item-action-href',
                ),
                $action_icon);
        }

        $count = null;
        if ($this->count) {
            $count = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-list-item-count',
                ),
                $this->count);
        }

        $icons = $this->getIcons();


        if($this->type === self::TYPE_DIVIDER) {
            return null;
        }

        $list_item = JavelinHtml::phutil_tag(
            $this->href ? 'a' : ($this->type === self::TYPE_DIVIDER ? 'hr' : 'div'),
            ArrayHelper::merge(array(
                'href' => $this->href,
                'class' => implode(' ', $classes),
                'meta' => $meta,
                'sigil' => $sigil,
                'target' => $this->getOpenInNewWindow() ? '_blank' : null,
                'rel' => $this->rel,
            ), $this->getLinkTagAttributes()),
            array(
                $aural,
                $icon,
                $icons,
                $this->renderChildren(),
                $name,
                $count,
            ));

        $subMenu = null;
        if (is_array($this->subListItems) && count($this->subListItems) > 0) {
            $isSubSelected = false;
            foreach ($this->subListItems as $subListItem) {
                $subListItem->getSelected() && $isSubSelected = true;
            }
            $subMenu = JavelinHtml::phutil_tag("ul", [
                "class" => "nav nav-group-sub",
                "style" => $isSubSelected ? "display: block" : "display: none",
            ], $this->subListItems);
        }


        return array($list_item, new PhutilSafeHTML($subMenu), $action_link);
    }
}
