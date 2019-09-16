<?php

namespace orangins\modules\guides\view;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorGuideItemView
 * @package orangins\modules\guides\view
 * @author 陈妙威
 */
final class PhabricatorGuideItemView extends OranginsObject
{

    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $iconBackground;
    /**
     * @var
     */
    private $skipHref;

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
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
     * @param $background
     * @return $this
     * @author 陈妙威
     */
    public function setIconBackground($background)
    {
        $this->iconBackground = $background;
        return $this;
    }

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setSkipHref($href)
    {
        $this->skipHref = $href;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return $this->description;
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
     * @return mixed
     * @author 陈妙威
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIconBackground()
    {
        return $this->iconBackground;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSkipHref()
    {
        return $this->skipHref;
    }


}
