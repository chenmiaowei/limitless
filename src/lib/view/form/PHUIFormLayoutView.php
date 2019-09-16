<?php

namespace orangins\lib\view\form;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * This provides the layout of an AphrontFormView without actually providing
 * the <form /> tag. Useful on its own for creating forms in other forms (like
 * dialogs) or forms which aren't submittable.
 */
final class PHUIFormLayoutView extends AphrontView
{

    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var
     */
    private $fullWidth;

    /**
     * @param $width
     * @return $this
     * @author 陈妙威
     */
    public function setFullWidth($width)
    {
        $this->fullWidth = $width;
        return $this;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param $text
     * @return PHUIFormLayoutView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function appendInstructions($text)
    {
        return $this->appendChild(
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'row aphront-form-instructions',
                ),
                $text));
    }

    /**
     * @param $remarkup
     * @return PHUIFormLayoutView
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function appendRemarkupInstructions($remarkup)
    {
        $view = (new AphrontFormView())
            ->setViewer($this->getViewer())
            ->newInstructionsRemarkupView($remarkup);

        return $this->appendInstructions($view);
    }

    /**
     * @return string
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function render()
    {
        $classes = $this->classes;
        $classes[] = 'phui-form-view';

        if ($this->fullWidth) {
            $classes[] = 'phui-form-full-width';
        }

        return JavelinHtml::tag('div', $this->renderChildren(), array(
                'class' => implode(' ', $classes),
            ));

    }
}
