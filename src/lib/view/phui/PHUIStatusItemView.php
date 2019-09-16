<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;

/**
 * Class PHUIStatusItemView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIStatusItemView extends AphrontTagView
{

    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $iconLabel;
    /**
     * @var
     */
    private $iconColor;
    /**
     * @var
     */
    private $target;
    /**
     * @var
     */
    private $note;
    /**
     * @var
     */
    private $highlighted;

    /**
     *
     */
    const ICON_ACCEPT = 'fa-check-circle';
    /**
     *
     */
    const ICON_REJECT = 'fa-times-circle';
    /**
     *
     */
    const ICON_LEFT = 'fa-chevron-circle-left';
    /**
     *
     */
    const ICON_RIGHT = 'fa-chevron-circle-right';
    /**
     *
     */
    const ICON_UP = 'fa-chevron-circle-up';
    /**
     *
     */
    const ICON_DOWN = 'fa-chevron-circle-down';
    /**
     *
     */
    const ICON_QUESTION = 'fa-question-circle';
    /**
     *
     */
    const ICON_WARNING = 'fa-exclamation-circle';
    /**
     *
     */
    const ICON_INFO = 'fa-info-circle';
    /**
     *
     */
    const ICON_ADD = 'fa-plus-circle';
    /**
     *
     */
    const ICON_MINUS = 'fa-minus-circle';
    /**
     *
     */
    const ICON_OPEN = 'fa-circle-o';
    /**
     *
     */
    const ICON_CLOCK = 'fa-clock-o';
    /**
     *
     */
    const ICON_STAR = 'fa-star';

    /**
     * @param $icon
     * @param null $color
     * @param null $label
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon, $color = null, $label = null)
    {
        $this->icon = $icon;
        $this->iconLabel = $label;
        $this->iconColor = $color;
        return $this;
    }

    /**
     * @param $target
     * @return $this
     * @author 陈妙威
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @param $note
     * @return $this
     * @author 陈妙威
     */
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @param $highlighted
     * @return $this
     * @author 陈妙威
     */
    public function setHighlighted($highlighted)
    {
        $this->highlighted = $highlighted;
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
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'tr';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        if ($this->highlighted) {
            $classes[] = 'phui-status-item-highlighted';
        }

        return array(
            'class' => $classes,
        );
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        $icon = null;
        if ($this->icon) {
            $icon = (new PHUIIconView())
                ->setIcon($this->icon . ' ' . $this->iconColor);

            if ($this->iconLabel) {
                JavelinHtml::initBehavior(new JavelinTooltipAsset());
                $icon->addSigil('has-tooltip');
                $icon->setMetadata(
                    array(
                        'tip' => $this->iconLabel,
                        'size' => 240,
                    ));
            }
        }

        $target_cell = JavelinHtml::phutil_tag(
            'td',
            array(
                'class' => 'phui-status-item-target',
            ),
            array(
                $icon,
                $this->target,
            ));

        $note_cell = JavelinHtml::phutil_tag(
            'td',
            array(
                'class' => 'phui-status-item-note',
            ),
            $this->note);

        return array(
            $target_cell,
            $note_cell,
        );
    }
}
