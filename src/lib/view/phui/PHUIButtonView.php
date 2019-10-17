<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 10:33 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\modules\widgets\javelin\JavelinPHUIDropdownBehaviorAsset;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;

/**
 * Class PHUIButtonView
 * @package orangins\modules\widgets\components
 * @author 陈妙威
 */
class PHUIButtonView extends AphrontTagView
{
    /**
     *
     */
    const BUTTONTYPE_DEFAULT = 'buttontype.default';
    /**
     *
     */
    const BUTTONTYPE_SIMPLE = 'buttontype.simple';

    /**
     *
     */
    const BUTTON_TYPE_LABELED = "btn-labeled";
    /**
     *
     */
    const BUTTON_TYPE_OUTLINE = "btn-outline";
    /**
     *
     */
    const SMALL = 'btn-sm';
    /**
     *
     */
    const BIG = 'btn-lg';

    /**
     * @var
     */
    private $size;
    /**
     * @var
     */
    private $text;
    /**
     * @var
     */
    private $subtext;
    /**
     * @var
     */
    private $color;
    /**
     * @var string
     */
    private $tag = 'button';
    /**
     * @var
     */
    private $dropdown;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $iconFirst;
    /**
     * @var null
     */
    private $href = null;
    /**
     * @var null
     */
    private $title = null;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $selected;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $tooltip;
    /**
     * @var
     */
    private $noCSS;
    /**
     * @var
     */
    private $hasCaret;
    /**
     * @var string
     */
    private $buttonType = self::BUTTONTYPE_DEFAULT;
    /**
     * @var
     */
    private $auralLabel;

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     * @return self
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     * @return self
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubtext()
    {
        return $this->subtext;
    }

    /**
     * @param mixed $subtext
     * @return self
     */
    public function setSubtext($subtext)
    {
        $this->subtext = $subtext;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     * @return self
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     * @return self
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDropdown()
    {
        return $this->dropdown;
    }

    /**
     * @param mixed $dropdown
     * @return self
     */
    public function setDropdown($dropdown)
    {
        $this->dropdown = $dropdown;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }


    /**
     * @return null
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param null $href
     * @return self
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @return null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param mixed $disabled
     * @return self
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * @param mixed $selected
     * @return self
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * @param mixed $tooltip
     * @return self
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNoCSS()
    {
        return $this->noCSS;
    }

    /**
     * @param mixed $noCSS
     * @return self
     */
    public function setNoCSS($noCSS)
    {
        $this->noCSS = $noCSS;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHasCaret()
    {
        return $this->hasCaret;
    }

    /**
     * @param mixed $hasCaret
     * @return self
     */
    public function setHasCaret($hasCaret)
    {
        $this->hasCaret = $hasCaret;
        return $this;
    }

    /**
     * @return string
     */
    public function getButtonType()
    {
        return $this->buttonType;
    }

    /**
     * @param string $buttonType
     * @return self
     */
    public function setButtonType($buttonType)
    {
        $this->buttonType = $buttonType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAuralLabel()
    {
        return $this->auralLabel;
    }

    /**
     * @param mixed $auralLabel
     * @return self
     */
    public function setAuralLabel($auralLabel)
    {
        $this->auralLabel = $auralLabel;
        return $this;
    }

    /**
     * @param $icon
     * @param bool $first
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon, $first = true)
    {
        if (!($icon instanceof PHUIIconView)) {
            $icon = (new PHUIIconView())
                ->addClass(PHUI::PADDING_SMALL_RIGHT)
                ->setIcon($icon);
        }
        $this->icon = $icon;
        $this->iconFirst = $first;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return $this->tag;
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
        $this->setDropdown(true);
        $metadata = $actions->getDropdownMenuMetadata();
        $this->setMetadata($metadata);
        return $this;
    }

    /**
     * @param $id
     * @return $this
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function setDropdownMenuID($id)
    {
        JavelinHtml::initBehavior(new JavelinPHUIDropdownBehaviorAsset());
        $this->addSigil('phui-dropdown-menu');
        $this->setMetadata(
            array(
                'menuID' => $id,
            ));

        return $this;
    }


    /**
     * @return array
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array(
            "btn bg-{$this->color} border-{$this->color}",
        );

        if ($this->size) {
            $classes[] = $this->size;
        }

        if ($this->dropdown) {
            $classes[] = 'dropdown';
        }

        if ($this->icon) {
            $classes[] = 'has-icon';
        }

        if ($this->text !== null) {
            $classes[] = 'has-text';
        }

        if ($this->iconFirst == false) {
            $classes[] = 'mr-0';
        }

        if ($this->disabled) {
            $classes[] = 'disabled';
        }

        if ($this->selected) {
            $classes[] = 'selected';
        }

        if ($this->dropdown || $this->getHasCaret()) {
            $classes[] = 'dropdown-toggle';
        }

        switch ($this->getButtonType()) {
            case self::BUTTONTYPE_DEFAULT:
                $classes[] = 'phui-button-default';
                break;
            case self::BUTTONTYPE_SIMPLE:
                $classes[] = 'phui-button-simple';
                break;
        }

        $sigil = null;
        $meta = null;
        if ($this->tooltip) {
            JavelinHtml::initBehavior(new JavelinTooltipAsset());
            $sigil = 'has-tooltip';
            $meta = array(
                'tip' => $this->tooltip,
            );
        }

        if ($this->noCSS) {
            $classes = array();
        }

        // See PHI823. If we aren't rendering a "<button>" or "<input>" tag,
        // give the tag we are rendering a "button" role as a hint to screen
        // readers.
        $role = null;
        if ($this->tag !== 'button' && $this->tag !== 'input') {
            $role = 'button';
        }

        $attrs = array(
            'class' => $classes,
            'href' => $this->href,
            'name' => $this->name,
            'title' => $this->title,
            'sigil' => $sigil,
            'meta' => $meta,
            'role' => $role,
        );

        if ($this->tag == 'input') {
            $attrs['type'] = 'submit';
            $attrs['value'] = $this->text;
        }

        return $attrs;
    }

    /**
     * @return array|\orangins\lib\response\AphrontResponse|null|string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        if ($this->tag === 'input') {
            return null;
        }

        $icon = $this->icon;
        $text = null;
        $subtext = null;

        if ($this->subtext) {
            $subtext = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-button-subtext',
            ), $this->subtext);
        }

        if ($this->text !== null) {
            $text = JavelinHtml::phutil_tag('span', array(
                'class' => 'phui-button-text',
            ), array(
                $this->text,
                $subtext,
            ));
        }


        $aural = null;
        if ($this->auralLabel !== null) {
            $aural = JavelinHtml::phutil_tag('span', array(
                'class' => 'aural-only',
            ), $this->auralLabel);
        }


        if ($this->iconFirst == true) {
            return array($aural, $icon, $text);
        } else {
            return array($aural, $text, $icon);
        }
    }
}