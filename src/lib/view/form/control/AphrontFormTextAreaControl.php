<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;

/**
 * @concrete-extensible
 */
class AphrontFormTextAreaControl extends AphrontFormControl
{

    /**
     *
     */
    const HEIGHT_VERY_SHORT = 'very-short';
    /**
     *
     */
    const HEIGHT_SHORT = 'short';
    /**
     *
     */
    const HEIGHT_VERY_TALL = 'very-tall';

    /**
     * @var
     */
    private $height;
    /**
     * @var
     */
    private $readOnly;
    /**
     * @var
     */
    private $customClass;
    /**
     * @var
     */
    private $placeHolder;
    /**
     * @var
     */
    private $sigil;

    /**
     * @param $sigil
     * @return $this
     * @author 陈妙威
     */
    public function setSigil($sigil)
    {
        $this->sigil = $sigil;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSigil()
    {
        return $this->sigil;
    }

    /**
     * @param $place_holder
     * @return $this
     * @author 陈妙威
     */
    public function setPlaceHolder($place_holder)
    {
        $this->placeHolder = $place_holder;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getPlaceHolder()
    {
        return $this->placeHolder;
    }

    /**
     * @param $height
     * @return $this
     * @author 陈妙威
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @param $read_only
     * @return $this
     * @author 陈妙威
     */
    public function setReadOnly($read_only)
    {
        $this->readOnly = $read_only;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-textarea';
    }

    /**
     * @param $custom_class
     * @return $this
     * @author 陈妙威
     */
    public function setCustomClass($custom_class)
    {
        $this->customClass = $custom_class;
        return $this;
    }

    /**
     * @return mixed|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {

        $height_class = null;
        switch ($this->height) {
            case self::HEIGHT_VERY_SHORT:
            case self::HEIGHT_SHORT:
            case self::HEIGHT_VERY_TALL:
                $height_class = 'aphront-textarea-' . $this->height;
                break;
        }

        $classes = array();
        $classes[] = 'form-control';
        $classes[] = $height_class;
        $classes[] = $this->customClass;
        $classes = trim(implode(' ', $classes));

        // NOTE: This needs to be string cast, because if we pass `null` the
        // tag will be self-closed and some browsers aren't thrilled about that.
        $value = (string)$this->getValue();

        // NOTE: We also need to prefix the string with a newline, because browsers
        // ignore a newline immediately after a <textarea> tag, so they'll eat
        // leading newlines if we don't do this. See T8707.
        $value = "\n" . $value;

        return JavelinHtml::phutil_tag(
            'textarea',
            array(
                'name' => $this->getName(),
                'disabled' => $this->getDisabled() ? 'disabled' : null,
                'readonly' => $this->getReadOnly() ? 'readonly' : null,
                'class' => $classes,
                'style' => $this->getControlStyle(),
                'id' => $this->getID(),
                'sigil' => $this->sigil,
                'placeholder' => $this->getPlaceHolder(),
            ),
            $value);
    }

}
