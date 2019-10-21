<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/29
 * Time: 10:41 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\form\control;


use orangins\lib\helpers\JavelinHtml;

/**
 * Class AphrontFormTextControl
 * @package orangins\modules\widgets\form
 * @author 陈妙威
 */
class AphrontFormTextControl extends AphrontFormControl
{
    /**
     * @var
     */
    private $disableAutocomplete;
    /**
     * @var
     */
    private $sigil;
    /**
     * @var
     */
    private $placeholder;

    /**
     * @param $disable
     * @return $this
     * @author 陈妙威
     */
    public function setDisableAutocomplete($disable)
    {
        $this->disableAutocomplete = $disable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getDisableAutocomplete()
    {
        return $this->disableAutocomplete;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @param $placeholder
     * @return $this
     * @author 陈妙威
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
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
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-text';
    }

    /**
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {

        return JavelinHtml::input('text', $this->getName(), $this->getValue(), array(
            'disabled' => $this->getDisabled() ? 'disabled' : null,
            'readonly' => $this->getReadOnly() ? 'readonly' : null,
            'autocomplete' => $this->getDisableAutocomplete() ? 'off' : null,
            'id' => $this->getID(),
            'sigil' => $this->getSigil(),
            'class' => 'form-control',
            'placeholder' => $this->getPlaceholder(),
        ));
    }
}