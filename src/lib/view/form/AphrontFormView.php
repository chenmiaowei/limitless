<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 1:06 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\form;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\view\AphrontView;
use orangins\lib\view\form\control\AphrontFormControl;

/**
 * Class AphrontFormView
 * @package orangins\lib\view\form
 * @author 陈妙威
 */
final class AphrontFormView extends AphrontView
{

    /**
     * @var
     */
    private $action;
    /**
     * @var string
     */
    private $method = 'POST';
    /**
     * @var
     */
    private $header;
    /**
     * @var array
     */
    private $data = array();
    /**
     * @var
     */
    private $encType;
    /**
     * @var
     */
    private $workflow;
    /**
     * @var
     */
    private $id;
    /**
     * @var array
     */
    private $sigils = array();
    /**
     * @var
     */
    private $metadata;
    /**
     * @var AphrontFormControl[]
     */
    private $controls = array();
    /**
     * @var bool
     */
    private $fullWidth = false;
    /**
     * @var array
     */
    private $classes = array();

    /**
     * @param $metadata
     * @return $this
     * @author 陈妙威
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $action
     * @return $this
     * @author 陈妙威
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param $method
     * @return $this
     * @author 陈妙威
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param $enc_type
     * @return $this
     * @author 陈妙威
     */
    public function setEncType($enc_type)
    {
        $this->encType = $enc_type;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function addHiddenInput($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
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
     * @param $full_width
     * @return $this
     * @author 陈妙威
     */
    public function setFullWidth($full_width)
    {
        $this->fullWidth = $full_width;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getFullWidth()
    {
        return $this->fullWidth;
    }

    /**
     * @param $text
     * @return AphrontFormView
     * @throws \yii\base\Exception
     * @throws \Exception
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
                array(
                    JavelinHtml::phutil_tag_div("col-lg-2"),
                    JavelinHtml::phutil_tag_div("col-lg-8",  array($text)),
                )));
    }

    /**
     * @param $remarkup
     * @return AphrontFormView
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function appendRemarkupInstructions($remarkup)
    {
        $view = $this->newInstructionsRemarkupView($remarkup);
        return $this->appendInstructions($view);
    }

    /**
     * @param $remarkup
     * @return PHUIRemarkupView
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function newInstructionsRemarkupView($remarkup)
    {
        $viewer = $this->getViewer();
        $view = new PHUIRemarkupView($viewer, $remarkup);

        $view->setRemarkupOptions(
            array(
                PHUIRemarkupView::OPTION_PRESERVE_LINEBREAKS => false,
            ));

        return $view;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function buildLayoutView()
    {
        foreach ($this->controls as $control) {
            $control->setViewer($this->getViewer());
            $control->willRender();
        }

        return (new PHUIFormLayoutView())
            ->setFullWidth($this->getFullWidth())
            ->appendChild($this->renderDataInputs())
            ->appendChild($this->renderChildren());
    }


    /**
     * Append a control to the form.
     *
     * This method behaves like @{method:appendChild}, but it only takes
     * controls. It will propagate some information from the form to the
     * control to simplify rendering.
     *
     * @param AphrontFormControl $control
     * @return AphrontFormView
     * @throws \yii\base\Exception
     */
    public function appendControl(AphrontFormControl $control)
    {
        $this->controls[] = $control;
        return $this->appendChild($control);
    }


    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {
        $layout = $this->buildLayoutView();

        if (!$this->hasViewer()) {
            throw new Exception(
                \Yii::t("app",
                    'You must pass the user to {0}.',[
                        __CLASS__
                    ]));
        }

        $sigils = $this->sigils;
        if ($this->workflow) {
            $sigils[] = 'workflow';
        }

        return JavelinHtml::phabricator_form(
            $this->getViewer(),
            array(
                'class' => implode(' ', $this->classes),
                'action' => $this->action,
                'method' => $this->method,
                'enctype' => $this->encType,
                'sigil' => $sigils ? implode(' ', $sigils) : null,
                'meta' => $this->metadata,
                'id' => $this->id,
            ),
            $layout->render());
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderDataInputs()
    {
        $inputs = array();
        foreach ($this->data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $inputs[] = phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => $key,
                    'value' => $value,
                ));
        }
        return $inputs;
    }

}
