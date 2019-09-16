<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 12:41 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\form\control;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIButtonView;
use Yii;

/**
 * Class AphrontFormSubmitControl
 * @package orangins\modules\widgets\form
 * @author 陈妙威
 */
final class AphrontFormSubmitControl extends AphrontFormControl
{

    /**
     * @var array
     */
    private $buttons = array();
    /**
     * @var array
     */
    private $sigils = array();

    /**
     * @param $href
     * @param null $label
     * @return $this
     * @author 陈妙威
     */
    public function addCancelButton($href, $label = null)
    {
        if (!$label) {
            $label = Yii::t('app', 'Cancel');
        }
        $button = (new PHUIButtonView())
            ->setTag('a')
            ->setHref($href)
            ->setText($label)
            ->addClass("text-muted btn-light")
            ->addClass(PHUI::MARGIN_MEDIUM_LEFT);
        $this->addButton($button);
        return $this;
    }

    /**
     * @param PHUIButtonView $button
     * @return $this
     * @author 陈妙威
     */
    public function addButton(PHUIButtonView $button)
    {
        $this->buttons[] = $button;
        return $this;
    }

    /**
     * @param $sigil
     * @return $this
     * @author 陈妙威
     */
    public function addSigil($sigil)
    {
        $this->sigils[] = $sigil;
        return $this;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-submit';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function renderInput()
    {
        $submit_button = null;
        if ($this->getValue()) {

            if ($this->sigils) {
                $sigils = $this->sigils;
            } else {
                $sigils = null;
            }

            $submit_button = JavelinHtml::phutil_tag(
                'button',
                array(
                    'type' => 'submit',
                    'name' => '__submit__',
                    'sigil' => $sigils,
                    'class' => 'btn bg-' . PhabricatorEnv::getEnvConfig("ui.widget-color"),
                    'disabled' => $this->getDisabled() ? 'disabled' : null,
                ),
                $this->getValue());
        }

        return JavelinHtml::phutil_tag("div", [
            "class" => "text-right"
        ], array(
            $submit_button,
            $this->buttons,
        ));
    }

}
