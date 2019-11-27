<?php

namespace orangins\lib\view\phui;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\PhabricatorAnchorView;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\widgets\javelin\JavelinRevealContentAsset;
use ReflectionException;
use Yii;

/**
 * Class PHUIObjectBoxView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIObjectBoxView extends AphrontTagView
{
    /**
     * 头部
     * @var PHUIHeaderView
     */
    private $header;
    /**
     * 头部名称文字
     * @var string
     */
    private $headerText;

    /**
     * @var
     */
    private $foooter;

    /**
     * @var
     */
    private $body;
    /**
     * @var
     */
    private $color;
    /**
     * @var
     */
    private $background;
    /**
     * @var array
     */
    private $tabGroups = array();
    /**
     * @var null
     */
    private $formErrors = null;
    /**
     * @var bool
     */
    private $formSaved = false;
    /**
     * @var
     */
    private $infoView;
    /**
     * @var
     */
    private $form;
    /**
     * @var PhabricatorApplicationTransactionValidationException
     */
    private $validationException;

    /**
     * @var
     */
    private $flush;
    /**
     * @var
     */
    private $actionListID;
    /**
     * @var
     */
    private $objectList;
    /**
     * @var
     */
    private $table;
    /**
     * @var bool
     */
    private $collapsed = false;

    /**
     * @var bool
     */
    private $enable_collapse = false;
    /**
     * @var
     */
    private $anchor;
    /**
     * @var PHUIPagerView
     */
    private $pager;

    /**
     * @var
     */
    private $showAction;
    /**
     * @var
     */
    private $hideAction;
    /**
     * @var
     */
    private $showHideHref;
    /**
     * @var
     */
    private $showHideContent;
    /**
     * @var
     */
    private $showHideOpen;

    /**
     * @var
     */
    private $bodyClass = ["card-body"];
    /**
     * @var
     */
    private $paddingNone = false;
    /**
     * @var array
     */
    private $propertyLists = array();

    /**
     *
     */
    const BLUE_PROPERTY = 'phui-box-blue-property';
    /**
     *
     */
    const WHITE_CONFIG = 'phui-box-white-config';
    /**
     *
     */
    const BACKGROUND_GREY = 'phui-box-grey';


    /**\
     * @param $class
     * @return PHUIObjectBoxView
     * @author 陈妙威
     */
    public function addBodyClass($class)
    {
        $this->bodyClass[] = $class;
        return $this;
    }

    /**
     * @param PHUIPropertyListView $property_list
     * @return $this
     * @author 陈妙威
     */
    public function addPropertyList(PHUIPropertyListView $property_list)
    {
        $this->propertyLists[] = $property_list;

        $action_list = $property_list->getActionList();
        if ($action_list) {
            $this->actionListID = JavelinHtml::generateUniqueNodeId();
            $action_list->setId($this->actionListID);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFoooter()
    {
        return $this->foooter;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $foooter
     * @return self
     */
    public function setFoooter($foooter)
    {
        $this->foooter = $foooter;
        return $this;
    }

    /**
     * @param mixed $body
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPaddingNone()
    {
        return $this->paddingNone;
    }

    /**
     * @param $paddingNone boolean
     * @return self
     */
    public function setPaddingNone($paddingNone)
    {
        if ($paddingNone) {
            $this->addBodyClass(PHUI::PADDING_NONE);
        }
        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setHeaderText($text)
    {
        $this->headerText = $text;
        return $this;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setBackground($color)
    {
        $this->background = $color;
        return $this;
    }

    /**
     * @param array $errors
     * @param null $title
     * @return $this
     * @author 陈妙威
     */
    public function setFormErrors(array $errors, $title = null)
    {
        if ($errors) {
            $this->formErrors = (new PHUIInfoView())
                ->setTitle($title)
                ->setErrors($errors);
        }
        return $this;
    }

    /**
     * @param $saved
     * @param null $text
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function setFormSaved($saved, $text = null)
    {
        if (!$text) {
            $text = Yii::t("app", 'Changes saved.');
        }
        if ($saved) {
            $save = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
                ->appendChild($text);
            $this->formSaved = $save;
        }
        return $this;
    }

    /**
     * @param PHUITabGroupView $view
     * @return $this
     * @author 陈妙威
     */
    public function addTabGroup(PHUITabGroupView $view)
    {
        $this->tabGroups[] = $view;
        return $this;
    }

    /**
     * @param PHUIInfoView $view
     * @return $this
     * @author 陈妙威
     */
    public function setInfoView(PHUIInfoView $view)
    {
        $this->infoView = $view;
        return $this;
    }

    /**
     * @param $form
     * @return $this
     * @author 陈妙威
     */
    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @param PHUIHeaderView $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader(PHUIHeaderView $header = null)
    {
        $this->header = $header;
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
     * @param $list
     * @return $this
     * @author 陈妙威
     */
    public function setObjectList($list)
    {
        $this->objectList = $list;
        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @author 陈妙威
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param $collapsed
     * @return $this
     * @author 陈妙威
     */
    public function setCollapsed($collapsed)
    {
        $this->collapsed = $collapsed;
        return $this;
    }

    /**
     * @param bool $enable_collapse
     * @return self
     */
    public function setEnableCollapse($enable_collapse)
    {
        $this->enable_collapse = $enable_collapse;
        return $this;
    }


    /**
     * @param PHUIPagerView $pager
     * @return $this
     * @author 陈妙威
     */
    public function setPager(PHUIPagerView $pager)
    {
        $this->pager = $pager;
        return $this;
    }

    /**
     * @param PhabricatorAnchorView $anchor
     * @return $this
     * @author 陈妙威
     */
    public function setAnchor(PhabricatorAnchorView $anchor)
    {
        $this->anchor = $anchor;
        return $this;
    }

    /**
     * @param $show
     * @param $hide
     * @param $content
     * @param $href
     * @param bool $open
     * @return $this
     * @author 陈妙威
     */
    public function setShowHide($show, $hide, $content, $href, $open = false)
    {
        $this->showAction = $show;
        $this->hideAction = $hide;
        $this->showHideContent = $content;
        $this->showHideHref = $href;
        $this->showHideOpen = $open;
        return $this;
    }

    /**
     * @param PhabricatorApplicationTransactionValidationException|null $ex
     * @return $this
     * @author 陈妙威
     */
    public function setValidationException(
        PhabricatorApplicationTransactionValidationException $ex = null)
    {
        $this->validationException = $ex;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'card';
//        $classes[] = 'phui-box-border';
//        $classes[] = 'phui-object-box';
//        $classes[] = 'mlt mll mlr';

        if ($this->color) {
            $classes[] = 'phui-object-box-' . $this->color;
        }

        if ($this->collapsed) {
            $classes[] = 'card-collapsed phui-object-box-collapsed';
        }

        if ($this->flush) {
            $classes[] = 'phui-object-box-flush';
        }

        if ($this->background) {
            $classes[] = $this->background;
        }

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array|AphrontResponse|string
     * @throws ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        $header = $this->header;

        if ($this->headerText) {
            $header = (new PHUIHeaderView())
                ->setHeader($this->headerText);
        }

        $elements = null;

        $showhide = null;
        if ($this->showAction !== null) {
            if (!$header) {
                $header = (new PHUIHeaderView());
            }

            JavelinHtml::initBehavior(new JavelinRevealContentAsset());

            $hide_action_id = JavelinHtml::generateUniqueNodeId();
            $show_action_id = JavelinHtml::generateUniqueNodeId();
            $content_id = JavelinHtml::generateUniqueNodeId();

            $hide_style = ($this->showHideOpen ? 'display: none;' : null);
            $show_style = ($this->showHideOpen ? null : 'display: none;');
            $hide_action = (new PHUIButtonView())
                ->setTag('a')
                ->addSigil('reveal-content')
                ->setID($hide_action_id)
                ->setStyle($hide_style)
                ->setIcon('fa-search')
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                ->setHref($this->showHideHref)
                ->setMetaData(
                    array(
                        'hideIDs' => array($hide_action_id),
                        'showIDs' => array($content_id, $show_action_id),
                    ))
                ->setText($this->showAction);

            $show_action = (new PHUIButtonView())
                ->setTag('a')
                ->addSigil('reveal-content')
                ->setStyle($show_style)
                ->setIcon('fa-search')
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                ->setHref('#')
                ->setID($show_action_id)
                ->setMetaData(
                    array(
                        'hideIDs' => array($content_id, $show_action_id),
                        'showIDs' => array($hide_action_id),
                    ))
                ->setText($this->hideAction);

            $header->addActionLink($hide_action);
            $header->addActionLink($show_action);

            $showhide = array(
                JavelinHtml::tag('div', $this->showHideContent, array(
                    'class' => 'phui-object-box-hidden-content',
                    'id' => $content_id,
                    'style' => $show_style,
                )),
            );
        }


        if ($this->actionListID) {
            $icon_id = JavelinHtml::generateUniqueNodeId();
            $icon = (new PHUIIconView())
                ->setIcon('fa-bars');
            $meta = array(
                'map' => array(
                    $this->actionListID => 'phabricator-action-list-toggle',
                    $icon_id => 'phuix-dropdown-open',
                ),
            );
            $mobile_menu = (new PHUIButtonView())
                ->setTag('a')
                ->setText(Yii::t("app", 'Actions'))
                ->setHref('#')
                ->setIcon($icon)
                ->addClass('phui-mobile-menu')
                ->setID($icon_id)
                ->addSigil('jx-toggle-class')
                ->setMetadata($meta);
            $header->addActionLink($mobile_menu);
        }

        if ($header) {
            $header->setEnableCollapse($this->enable_collapse);
            $header->setCollapsed($this->collapsed);
        }


        $ex = $this->validationException;
        $exception_errors = null;
        if ($ex) {
            $messages = array();
            foreach ($ex->getErrors() as $error) {
                $messages[] = $error->getMessage();
            }
            if ($messages) {
                $exception_errors = (new PHUIInfoView())
                    ->setErrors($messages);
            }
        }

        if ($this->propertyLists) {
            $lists = new PHUIPropertyGroupView();

            $ii = 0;
            foreach ($this->propertyLists as $list) {
                if ($ii > 0 || $this->tabGroups) {
                    $list->addClass('phui-property-list-section-noninitial');
                }

                $lists->addPropertyList($list);
                $ii++;
            }
        } else {
            $lists = null;
        }

        $pager = null;
        if ($this->pager) {
            if ($this->pager->willShowPagingControls()) {
                $pager = JavelinHtml::phutil_tag_div('phui-object-box-pager', $this->pager);
            }
        }


        $bodyContent = [
            $this->infoView,
            $this->formErrors,
            $this->formSaved,
            $exception_errors,
            $this->form,
            $this->tabGroups,
            $showhide,
            ($this->showHideOpen == true ? $this->anchor : null),
            $lists,
            $this->table,
            $pager,
            $this->renderChildren()
        ];
        if ($this->objectList) {
            $bodyContent[] = $this->objectList;
        }

        if (preg_match("/(bg\-[^s]*)/", implode(" ", $this->getClasses()), $match)) {
            $headerClass = $match[1];
        } else {
            $headerClass = 'bg-light';
        }
        $header_div = $header ? JavelinHtml::phutil_tag_div("card-header {$headerClass}", [
            ($this->showHideOpen == false ? $this->anchor : null),
            $header,
        ]) : null;

        $footer_div = $this->getFoooter() ? JavelinHtml::phutil_tag_div("card-footer", [
            $this->getFoooter(),
        ]) : null;
        $body_div = $this->getBody() ? JavelinHtml::phutil_tag_div("card-body", [
            $this->getBody(),
        ]) : null;
        $content = array(
            $header_div,
            JavelinHtml::phutil_tag("div", [
                "class" => implode(" ", $this->bodyClass),
                "style" => $this->collapsed ? "display: none;" : "",
            ], $bodyContent),
            $body_div,
            $footer_div,

        );
        return $content;
    }
}
