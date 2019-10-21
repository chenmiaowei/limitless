<?php

namespace orangins\lib\view\form\control;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\modules\file\iconset\PhabricatorIconSet;
use orangins\modules\widgets\javelin\JavelinChooseControlAsset;
use ReflectionException;

/**
 * Class PHUIFormIconSetControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class PHUIFormIconSetControl extends AphrontFormControl
{

    /**
     * @var PhabricatorIconSet
     */
    private $iconSet;

    /**
     * @param PhabricatorIconSet $icon_set
     * @return $this
     * @author 陈妙威
     */
    public function setIconSet(PhabricatorIconSet $icon_set)
    {
        $this->iconSet = $icon_set;
        return $this;
    }

    /**
     * @return PhabricatorIconSet
     * @author 陈妙威
     */
    public function getIconSet()
    {
        return $this->iconSet;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'phui-form-iconset-control';
    }

    /**
     * @return mixed
     * @throws ReflectionException
      *@throws Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        JavelinHtml::initBehavior(new JavelinChooseControlAsset());

        $set = $this->getIconSet();

        $input_id = JavelinHtml::generateUniqueNodeId();
        $display_id = JavelinHtml::generateUniqueNodeId();

        $is_disabled = $this->getDisabled();

        $classes = array();
        $classes[] = 'btn';
        $classes[] = 'btn-xs';
        $classes[] = 'bg-' . PhabricatorEnv::getEnvConfig("ui.widget-color");

        if ($is_disabled) {
            $classes[] = 'disabled';
        }

        $button = JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => '#',
                'class' => implode(' ', $classes),
                'sigil' => 'phui-form-iconset-button',
            ),
            $set->getChooseButtonText());

        $icon = $set->getIcon($this->getValue());
        if ($icon) {
            $display = $set->renderIconForControl($icon);
        } else {
            $display = null;
        }

        $display_cell = JavelinHtml::phutil_tag(
            'td',
            array(
                'class' => 'phui-form-iconset-display-cell pt-1',
                'id' => $display_id,
            ),
            $display);

        $button_cell = JavelinHtml::phutil_tag(
            'td',
            array(
                'class' => 'phui-form-iconset-button-cell pt-1 pl-3',
            ),
            $button);

        $row = JavelinHtml::phutil_tag(
            'tr',
            array(),
            array($display_cell, $button_cell));

        $layout = JavelinHtml::phutil_tag(
            'table',
            array(
                'class' => 'phui-form-iconset-table',
                'sigil' => 'phui-form-iconset',
                'meta' => array(
                    'uri' => $set->getSelectURI(),
                    'inputID' => $input_id,
                    'displayID' => $display_id,
                ),
            ),
            $row);

        $hidden_input = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'disabled' => ($is_disabled ? 'disabled' : null),
                'name' => $this->getName(),
                'value' => $this->getValue(),
                'id' => $input_id,
            ));

        return array(
            $hidden_input,
            $layout,
        );
    }

}
