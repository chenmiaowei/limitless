<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class PHUICrumbView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUICrumbView extends AphrontView
{

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $isLastCrumb;
    /**
     * @var
     */
    private $workflow;
    /**
     * @var
     */
    private $aural;
    /**
     * @var
     */
    private $alwaysVisible;

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
     * Make this crumb always visible, even on devices where it would normally
     * be hidden.
     *
     * @param bool True to make the crumb always visible.
     * @return static
     */
    public function setAlwaysVisible($always_visible)
    {
        $this->alwaysVisible = $always_visible;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAlwaysVisible()
    {
        return $this->alwaysVisible;
    }

    /**
     * @param $workflow
     * @return $this
     * @author 陈妙威
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;
        return $this;
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
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }

    /**
     * @param $is_last_crumb
     * @return $this
     * @author 陈妙威
     */
    public function setIsLastCrumb($is_last_crumb)
    {
        $this->isLastCrumb = $is_last_crumb;
        return $this;
    }

    /**
     * @return array|string
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function render()
    {
        $classes = array(
            'breadcrumb-item',
        );

        $aural = null;
        if ($this->aural !== null) {
            $aural = JavelinHtml::tag('span', $this->aural, array(
                'aural' => true,
            ));
        }

        $icon = null;
        if ($this->icon) {
            $classes[] = 'phui-crumb-has-icon';
            $icon = (new PHUIIconView())
                ->addClass("pr-2")
                ->setIcon($this->icon);
        }

        // Surround the crumb name with spaces so that double clicking it only
        // selects the crumb itself.
        $name = array(' ', $this->name);

        $name = JavelinHtml::tag('span', $name, array(
            'class' => 'phui-crumb-name',
        ));

        // Because of text-overflow and safari, put the second space on the
        // outside of the element.
        $name = array($name, ' ');


        if ($this->getAlwaysVisible()) {
            $classes[] = 'phui-crumb-always-visible';
        }

        $tag = JavelinHtml::tag($this->href ? 'a' : 'span', array($aural, $icon, $name), array(
            'sigil' => $this->workflow ? 'workflow' : null,
            'href' => $this->href,
            'class' => implode(' ', $classes),
        ));

        return array($tag);
    }
}
