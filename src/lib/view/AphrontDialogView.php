<?php

namespace orangins\lib\view;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontResponseProducerInterface;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use Exception;
use Yii;

/**
 * Class AphrontDialogView
 * @package orangins\lib\view
 * @author 陈妙威
 */
final class AphrontDialogView extends AphrontView implements AphrontResponseProducerInterface
{

    /**
     * @var
     */
    private $title;
    /**
     * @var array
     */
    private $classes = array('card p-0');
    /**
     * @var array
     */
    private $bodyClasses = array('card-body');
    /**
     * @var
     */
    private $shortTitle;
    /**
     * @var
     */
    private $submitButton;
    /**
     * @var
     */
    private $cancelURI;
    /**
     * @var string
     */
    private $cancelText = 'Cancel';
    /**
     * @var
     */
    private $submitURI;
    /**
     * @var array
     */
    private $hidden = array();
    /**
     * @var
     */
    private $class;
    /**
     * @var bool
     */
    private $renderAsForm = true;
    /**
     * @var
     */
    private $formID;
    /**
     * @var array
     */
    private $footers = array();
    /**
     * @var
     */
    private $isStandalone;
    /**
     * @var string
     */
    private $method = 'POST';
    /**
     * @var
     */
    private $disableWorkflowOnSubmit;
    /**
     * @var
     */
    private $disableWorkflowOnCancel;
    /**
     * @var string
     */
    private $width = 'default';
    /**
     * @var array
     */
    private $errors = array();
    /**
     * @var
     */
    private $flush;
    /**
     * @var
     */
    private $validationException;
    /**
     * @var
     */
    private $objectList;
    /**
     * @var
     */
    private $resizeX;
    /**
     * @var
     */
    private $resizeY;

    /**
     * @var PHUIButtonView[]
     */
    private $actionLinks = array();
    /**
     *
     */
    const WIDTH_DEFAULT = 'wmin-lg-600';
    /**
     *
     */
    const WIDTH_FORM = 'wmin-lg-600';
    /**
     *
     */
    const WIDTH_FULL = 'wmin-lg-600';

    /**
     * @param PHUIButtonView $button
     * @return $this
     * @author 陈妙威
     */
    public function addActionLink(PHUIButtonView $button)
    {
        $this->actionLinks[] = $button;
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
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addBodyClass($class)
    {
        $this->bodyClasses[] = $class;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getClasses()
    {
        return $this->classes;
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
     * @param $is_standalone
     * @return $this
     * @author 陈妙威
     */
    public function setIsStandalone($is_standalone)
    {
        $this->isStandalone = $is_standalone;
        return $this;
    }

    /**
     * @param array $errors
     * @return $this
     * @author 陈妙威
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsStandalone()
    {
        return $this->isStandalone;
    }

    /**
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitURI($uri)
    {
        $this->submitURI = $uri;
        return $this;
    }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $short_title
     * @return $this
     * @author 陈妙威
     */
    public function setShortTitle($short_title)
    {
        $this->shortTitle = $short_title;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getShortTitle()
    {
        return $this->shortTitle;
    }

    /**
     * @param $resize_y
     * @return $this
     * @author 陈妙威
     */
    public function setResizeY($resize_y)
    {
        $this->resizeY = $resize_y;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getResizeY()
    {
        return $this->resizeY;
    }

    /**
     * @param $resize_x
     * @return $this
     * @author 陈妙威
     */
    public function setResizeX($resize_x)
    {
        $this->resizeX = $resize_x;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getResizeX()
    {
        return $this->resizeX;
    }

    /**
     * @param null $text
     * @return $this
     * @author 陈妙威
     */
    public function addSubmitButton($text = null)
    {
        if (!$text) {
            $text = Yii::t("app", 'Okay');
        }

        $this->submitButton = $text;
        return $this;
    }

    /**
     * @param $uri
     * @param null $text
     * @return $this
     * @author 陈妙威
     */
    public function addCancelButton($uri, $text = null)
    {
        if (!$text) {
            $text = Yii::t("app", 'Cancel');
        }

        $this->cancelURI = $uri;
        $this->cancelText = $text;
        return $this;
    }

    /**
     * @param $footer
     * @return $this
     * @author 陈妙威
     */
    public function addFooter($footer)
    {
        $this->footers[] = $footer;
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
        if (is_array($value)) {
            foreach ($value as $hidden_key => $hidden_value) {
                $this->hidden[] = array($key . '[' . $hidden_key . ']', $hidden_value);
            }
        } else {
            $this->hidden[] = array($key, $value);
        }
        return $this;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @param $flush
     * @return $this
     * @author 陈妙威
     */
    public function setFlush($flush)
    {
        $this->flush = $flush;
        return $this;
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function setRenderDialogAsDiv()
    {
        // TODO: This API is awkward.
        $this->renderAsForm = false;
        return $this;
    }

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setFormID($id)
    {
        $this->formID = $id;
        return $this;
    }

    /**
     * @param $width
     * @return $this
     * @author 陈妙威
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @param PHUIObjectItemListView $list
     * @return AphrontDialogView
     * @throws Exception
     * @author 陈妙威
     */
    public function setObjectList(PHUIObjectItemListView $list)
    {
        $this->objectList = true;
        $box = (new PHUIObjectBoxView())
            ->setObjectList($list);
        return $this->appendChild($box);
    }

    /**
     * @param $paragraph
     * @return AphrontDialogView
     * @throws Exception
     * @author 陈妙威
     */
    public function appendParagraph($paragraph)
    {

        return $this->appendChild(
            JavelinHtml::phutil_tag_div("row", [
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'col-lg-2',
                    )),
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'col-lg-8 text-muted aphront-dialog-view-paragraph',
                    ),
                    $paragraph)
            ]));
    }

    /**
     * @param array $items
     * @return AphrontDialogView
     * @throws Exception
     * @author 陈妙威
     */
    public function appendList(array $items)
    {
        $listitems = array();
        foreach ($items as $item) {
            $listitems[] = JavelinHtml::phutil_tag(
                'li',
                array(
                    'class' => 'remarkup-list-item',
                ),
                $item);
        }
        return $this->appendChild(
            JavelinHtml::phutil_tag(
                'ul',
                array(
                    'class' => 'remarkup-list',
                ),
                $listitems));
    }

    /**
     * @param AphrontFormView $form
     * @return AphrontDialogView
     * @throws Exception
     * @author 陈妙威
     */
    public function appendForm(AphrontFormView $form)
    {
        return $this->appendChild($form->buildLayoutView());
    }

    /**
     * @param $disable_workflow_on_submit
     * @return $this
     * @author 陈妙威
     */
    public function setDisableWorkflowOnSubmit($disable_workflow_on_submit)
    {
        $this->disableWorkflowOnSubmit = $disable_workflow_on_submit;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisableWorkflowOnSubmit()
    {
        return $this->disableWorkflowOnSubmit;
    }

    /**
     * @param $disable_workflow_on_cancel
     * @return $this
     * @author 陈妙威
     */
    public function setDisableWorkflowOnCancel($disable_workflow_on_cancel)
    {
        $this->disableWorkflowOnCancel = $disable_workflow_on_cancel;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisableWorkflowOnCancel()
    {
        return $this->disableWorkflowOnCancel;
    }

    /**
     * @param PhabricatorApplicationTransactionValidationException|null $ex
     * @return $this
     * @author 陈妙威
     */
    public function setValidationException(PhabricatorApplicationTransactionValidationException $ex = null)
    {
        $this->validationException = $ex;
        return $this;
    }

    /**
     * @return \PhutilSafeHTML|mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function render()
    {
        $buttons = array();
        if ($this->submitButton) {
            $meta = array();
            if ($this->disableWorkflowOnSubmit) {
                $meta['disableWorkflow'] = true;
            }

            $buttons[] = JavelinHtml::phutil_tag(
                'button',
                array(
                    'name' => '__submit__',
                    'sigil' => '__default__',
                    'type' => 'submit',
                    'meta' => $meta,
                    'class' => 'btn btn-sm bg-' . PhabricatorEnv::getEnvConfig("ui.widget-color"),
                ),
                $this->submitButton);
        }

        if ($this->cancelURI) {
            $meta = array();
            if ($this->disableWorkflowOnCancel) {
                $meta['disableWorkflow'] = true;
            }

            $buttons[] = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => $this->cancelURI,
                    'class' => 'btn  btn-light ml-2',
                    'name' => '__cancel__',
                    'sigil' => 'jx-workflow-button',
                    'meta' => $meta,
                ),
                $this->cancelText);
        }

        if (!$this->hasViewer()) {
            throw new Exception(
                Yii::t("app",
                    'You must call {0} when rendering an {1}.',
                    [
                        'setViewer()',
                        __CLASS__
                    ]));
        }

        $classes = array();
        $classes[] = 'aphront-dialog-view';
        $classes[] = $this->class;
        if ($this->flush) {
            $classes[] = 'aphront-dialog-flush';
        }

        if($this->width) {
            $classes[] = $this->width;
        }

        if ($this->isStandalone) {
            $classes[] = 'aphront-dialog-view-standalone';
        }

        if ($this->objectList) {
            $classes[] = 'aphront-dialog-object-list';
        }

        $attributes = array(
            'class' => implode(' ', $classes),
            'sigil' => 'jx-dialog',
            'role' => 'dialog',
        );

        $form_attributes = array(
            'action' => $this->submitURI,
            'method' => $this->method,
            'id' => $this->formID,
        );

        $hidden_inputs = array();
        $hidden_inputs[] = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => '__dialog__',
                'value' => '1',
            ));

        foreach ($this->hidden as $desc) {
            list($key, $value) = $desc;
            $hidden_inputs[] = JavelinHtml::phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => $key,
                    'value' => $value,
                    'sigil' => 'aphront-dialog-application-input',
                ));
        }

        if (!$this->renderAsForm) {
            $buttons = array(JavelinHtml::phabricator_form(
                $this->getViewer(),
                $form_attributes,
                array_merge($hidden_inputs, $buttons)),
            );
        }

        $children = $this->renderChildren();

        $errors = $this->errors;

        $ex = $this->validationException;
        $exception_errors = null;
        if ($ex) {
            foreach ($ex->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }
        }

        if ($errors) {
            $children = array(
                (new PHUIInfoView())->setErrors($errors),
                $children,
            );
        }

        $header = new PHUIHeaderView();
        $header->setHeader($this->title);
        $header->setActionLinks($this->actionLinks);

        $footer = null;
        if ($this->footers) {
            $footer = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'aphront-dialog-foot',
                ),
                $this->footers);
        }

        $resize = null;
        if ($this->resizeX || $this->resizeY) {
            $resize = JavelinHtml::phutil_tag('div', array(
                'class' => 'aphront-dialog-resize',
                'sigil' => 'jx-dialog-resize',
                'meta' => array(
                    'resizeX' => $this->resizeX,
                    'resizeY' => $this->resizeY,
                ),
            ));
        }

        $tail = null;
        if ($buttons || $footer) {
            $tail = array(
                JavelinHtml::phutil_tag_div("", $footer),
                JavelinHtml::phutil_tag_div("", $buttons),
                $resize,
            );
        }


        $content = array(
            JavelinHtml::phutil_tag("div", [
                "class" => "card-header bg-light",
            ], array($header)),
            JavelinHtml::phutil_tag("div", [
                "class" => implode(" ", $this->bodyClasses),
            ], array(
                JavelinHtml::phutil_tag('div',
                    array(
                        'class' => 'aphront-dialog-body phabricator-remarkup grouped',
                    ),
                    $children)
            )),
            JavelinHtml::phutil_tag("div", [
                "class" => "card-footer d-flex justify-content-between align-items-center",
            ], array(
                $tail
            )),
        );

        if ($this->renderAsForm) {
            $response = JavelinHtml::phabricator_form(
                $this->getViewer(),
                $form_attributes + $attributes,
                array($hidden_inputs, $content));
        } else {
            $response = JavelinHtml::phutil_tag(
                'div',
                $attributes,
                $content);
        }

        return JavelinHtml::phutil_tag("div", [
            "class" => implode(" ", $this->getClasses())
        ], array(
            $response
        ));
    }


    /* -(  AphrontResponseProducerInterface  )----------------------------------- */


    /**
     * @return AphrontDialogResponse|\orangins\lib\response\AphrontResponse
     * @author 陈妙威
     */
    public function produceAphrontResponse()
    {
        return (new AphrontDialogResponse())
            ->setDialog($this);
    }

}
