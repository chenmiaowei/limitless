<?php

namespace orangins\modules\search\engine;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;

/**
 * Class PhabricatorProfileMenuItemView
 * @package orangins\modules\search\engine
 * @author 陈妙威
 */
final class PhabricatorProfileMenuItemView
    extends OranginsObject
{

    /**
     * @var
     */
    private $config;
    /**
     * @var
     */
    private $uri;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $iconImage;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $tooltip;
    /**
     * @var array
     */
    private $actions = array();

    /**
     * @var array
     */
    private $subListItems = array();
    /**
     * @var array
     */
    private $counts = array();
    /**
     * @var array
     */
    private $images = array();
    /**
     * @var array
     */
    private $progressBars = array();
    /**
     * @var
     */
    private $isExternalLink;
    /**
     * @var
     */
    private $specialType;

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return $this
     * @author 陈妙威
     */
    public function setMenuItemConfiguration(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return PhabricatorProfileMenuItemConfiguration
     * @author 陈妙威
     */
    public function getMenuItemConfiguration()
    {
        return $this->config;
    }

    /**
     * @param array $subListItems
     * @return self
     */
    public function setSubListItems($subListItems)
    {
        $this->subListItems = $subListItems;
        return $this;
    }

    /**
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setURI($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getURI()
    {
        return $this->uri;
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
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
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
     * @param $icon_image
     * @return $this
     * @author 陈妙威
     */
    public function setIconImage($icon_image)
    {
        $this->iconImage = $icon_image;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIconImage()
    {
        return $this->iconImage;
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
     * @return mixed
     * @author 陈妙威
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * @param $uri
     * @return null
     * @author 陈妙威
     */
    public function newAction($uri)
    {
        $this->actions[] = $uri;
        return null;
    }

    /**
     * @param $count
     * @return null
     * @author 陈妙威
     */
    public function newCount($count)
    {
        $this->counts[] = $count;
        return null;
    }

    /**
     * @param $src
     * @return null
     * @author 陈妙威
     */
    public function newProfileImage($src)
    {
        $this->images[] = $src;
        return null;
    }

    /**
     * @param $bar
     * @return null
     * @author 陈妙威
     */
    public function newProgressBar($bar)
    {
        $this->progressBars[] = $bar;
        return null;
    }

    /**
     * @param $is_external
     * @return $this
     * @author 陈妙威
     */
    public function setIsExternalLink($is_external)
    {
        $this->isExternalLink = $is_external;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsExternalLink()
    {
        return $this->isExternalLink;
    }

    /**
     * @param $is_label
     * @return PhabricatorProfileMenuItemView
     * @author 陈妙威
     */
    public function setIsLabel($is_label)
    {
        return $this->setSpecialType('label');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsLabel()
    {
        return $this->isSpecialType('label');
    }

    /**
     * @param $is_divider
     * @return PhabricatorProfileMenuItemView
     * @author 陈妙威
     */
    public function setIsDivider($is_divider)
    {
        return $this->setSpecialType('divider');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsDivider()
    {
        return $this->isSpecialType('divider');
    }

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    private function setSpecialType($type)
    {
        $this->specialType = $type;
        return $this;
    }

    /**
     * @param $type
     * @return bool
     * @author 陈妙威
     */
    private function isSpecialType($type)
    {
        return ($this->specialType === $type);
    }

    /**
     * @return PHUIListItemView
     * @throws \Exception
     * @author 陈妙威
     */
    public function newListItemView()
    {
        $view = (new PHUIListItemView())
            ->setName($this->getName());

        $uri = $this->getURI();
        if (strlen($uri)) {
            if ($this->getIsExternalLink()) {
                if (!PhabricatorEnv::isValidURIForLink($uri)) {
                    $uri = '#';
                }
                $view->setRel('noreferrer');
            }

            $view->setHref($uri);
        }

        $icon = $this->getIcon();
        if ($icon) {
            $view->setIcon($icon);
        }

        $icon_image = $this->getIconImage();
        if ($icon_image) {
            $view->setProfileImage($icon_image);
        }

        if ($this->getDisabled()) {
            $view->setDisabled(true);
        }

        if ($this->getIsLabel()) {
            $view->setType(PHUIListItemView::TYPE_LABEL);
        }

        if ($this->getIsDivider()) {
            $view
                ->setType(PHUIListItemView::TYPE_DIVIDER)
                ->addClass('phui-divider');
        }

        $tooltip = $this->getTooltip();
        if (strlen($tooltip)) {
            $view->setTooltip($tooltip);
        }

        if ($this->images) {
//            require_celerity_resource('people-picture-menu-item-css');
            foreach ($this->images as $image_src) {
                $classes = array();
                $classes[] = 'people-menu-image w-100 p-2 people-menu-image';

                if ($this->getDisabled()) {
                    $classes[] = 'phui-image-disabled';
                }

                $image = phutil_tag(
                    'img',
                    array(
                        'src' => $image_src,
                        'class' => implode(' ', $classes),
                    ));

                $image = phutil_tag(
                    'div',
                    array(
                        'class' => 'people-menu-image-container',
                    ),
                    $image);

                $view->appendChild($image);
            }
        }

        foreach ($this->counts as $count) {
            $view->appendChild(
                phutil_tag(
                    'span',
                    array(
                        'class' => 'phui-list-item-count',
                    ),
                    $count));
        }

        foreach ($this->actions as $action) {
            $view->setActionIcon('fa-pencil', $action);
        }

        foreach ($this->progressBars as $bar) {
            $view->appendChild($bar);
        }

        if(is_array($this->subListItems) && count($this->subListItems) > 0) {
            $view->setSubListItems($this->subListItems);
        }

        return $view;
    }
}
