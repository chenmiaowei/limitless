<?php

namespace orangins\lib\view\control;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\typeahead\view\PhabricatorTypeaheadTokenView;
use orangins\lib\view\AphrontView;

/**
 * Class AphrontTokenizerTemplateView
 * @package orangins\lib\view\control
 * @author 陈妙威
 */
final class AphrontTokenizerTemplateView extends AphrontView
{

    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $browseURI;
    /**
     * @var
     */
    private $initialValue;

    /**
     * @param $browse_uri
     * @return $this
     * @author 陈妙威
     */
    public function setBrowseURI($browse_uri)
    {
        $this->browseURI = $browse_uri;
        return $this;
    }

    /**
     * @param string $id
     * @return $this|AphrontView
     * @author 陈妙威
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param array $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue(array $value)
    {
        assert_instances_of($value, PhabricatorTypeaheadTokenView::class);
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValue()
    {
        return $this->value;
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
     * @param array $initial_value
     * @return $this
     * @author 陈妙威
     */
    public function setInitialValue(array $initial_value)
    {
        $this->initialValue = $initial_value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInitialValue()
    {
        return $this->initialValue;
    }

    /**
     * @return string
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function render()
    {
//        require_celerity_resource('aphront-tokenizer-control-css');
        $id = $this->id;
        $name = $this->getName();
        $tokens = nonempty($this->getValue(), array());

        $input = JavelinHtml::input('text', $name, null, array(
            'mustcapture' => true,
            'class' => 'jx-tokenizer-input',
            'sigil' => 'tokenizer-input',
            'style' => 'width: 0px;',
            'disabled' => 'disabled',
        ));

        $content = $tokens;
        $content[] = $input;
        $content[] = JavelinHtml::tag('div', '', array('style' => 'clear: both;'));

        $container = JavelinHtml::tag('div', $content, array(
            'id' => $id,
            'class' => 'form-control jx-tokenizer-container',
            'sigil' => 'tokenizer-container',
        ));

        $icon = (new PHUIIconView())
            ->setIcon('fa-search');

        $browse = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon($icon)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->addClass('input-group-append tokenizer-browse-button')
            ->addSigil('tokenizer-browse');

        $classes = array();
        $classes[] = 'input-group';
        $classes[] = 'jx-tokenizer-frame mt-1 mb-1';

        if ($this->browseURI) {
            $classes[] = 'has-browse';
        }

        $initial = array();
        $initial_value = $this->getInitialValue();
        if ($initial_value) {
            foreach ($this->getInitialValue() as $value) {
                $initial[] = JavelinHtml::input('hidden', $name . '_initial[]', $value);
            }
        }

        $frame = JavelinHtml::tag('div', array(
            $container,
            $browse,
            $initial,
        ), array(
            'class' => implode(' ', $classes),
            'sigil' => 'tokenizer-frame',
        ));

        return $frame;
    }
}
