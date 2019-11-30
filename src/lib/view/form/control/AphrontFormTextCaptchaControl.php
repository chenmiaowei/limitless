<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/29
 * Time: 10:41 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\form\control;


use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\widgets\javelin\JavelinTextCaptchaAsset;

/**
 * Class AphrontFormTextControl
 * @package orangins\modules\widgets\form
 * @author 陈妙威
 */
class AphrontFormTextCaptchaControl extends AphrontFormControl
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
     * @var string
     */
    private $buttonText = '获取验证码';

    /**
     * @var string
     */
    private $url;

    /**
     * @param string $url
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param string $buttonText
     * @return self
     */
    public function setButtonText($buttonText)
    {
        $this->buttonText = $buttonText;
        return $this;
    }



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
    public function getID()
    {
        $ID = parent::getID();
        if (!$ID) {
            $ID = JavelinHtml::generateUniqueNodeId();
            parent::setID($ID);
        }
        return $ID;
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
        $buttonID = JavelinHtml::generateUniqueNodeId();
        $textID = $this->getID();
        JavelinHtml::initBehavior(new JavelinTextCaptchaAsset(), [
            'buttonText' => $this->buttonText,
            'url' => $this->url,
            'buttonId' => $buttonID,
            'textId' => $textID,
        ]);
        return JavelinHtml::phutil_tag('div', [
            'class' => 'input-group'
        ], [
            JavelinHtml::input('text', $this->getName(), $this->getValue(), array(
                'disabled' => $this->getDisabled() ? 'disabled' : null,
                'readonly' => $this->getReadOnly() ? 'readonly' : null,
                'autocomplete' => $this->getDisableAutocomplete() ? 'off' : null,
                'id' => $this->getID(),
                'sigil' => $this->getSigil(),
                'class' => 'form-control',
                'placeholder' => $this->getPlaceholder(),
            )),
            JavelinHtml::phutil_tag("div", [
                'class' => 'input-group-append'
            ], [
                (new PHUIButtonView())
                    ->setTag('a')
                    ->setID($buttonID)
                    ->setText($this->buttonText)
                    ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ])
        ]);
    }
}