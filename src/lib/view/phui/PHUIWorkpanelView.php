<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIWorkpanelView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIWorkpanelView extends AphrontTagView
{

    /**
     * @var array
     */
    private $cards = array();
    /**
     * @var
     */
    private $header;
    /**
     * @var null
     */
    private $subheader = null;
    /**
     * @var
     */
    private $footerAction;
    /**
     * @var array
     */
    private $headerActions = array();
    /**
     * @var
     */
    private $headerTag;
    /**
     * @var
     */
    private $headerIcon;
    /**
     * @var
     */
    private $href;

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setHeaderIcon($icon)
    {
        $this->headerIcon = $icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeaderIcon()
    {
        return $this->headerIcon;
    }

    /**
     * @param PHUIObjectItemListView $cards
     * @return $this
     * @author 陈妙威
     */
    public function setCards(PHUIObjectItemListView $cards)
    {
        $this->cards[] = $cards;
        return $this;
    }

    /**
     * @param $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param $subheader
     * @return $this
     * @author 陈妙威
     */
    public function setSubheader($subheader)
    {
        $this->subheader = $subheader;
        return $this;
    }

    /**
     * @param PHUIListItemView $footer_action
     * @return $this
     * @author 陈妙威
     */
    public function setFooterAction(PHUIListItemView $footer_action)
    {
        $this->footerAction = $footer_action;
        return $this;
    }

    /**
     * @param PHUIIconView $action
     * @return $this
     * @author 陈妙威
     */
    public function addHeaderAction(PHUIIconView $action)
    {
        $this->headerActions[] = $action;
        return $this;
    }

    /**
     * @param PHUITagView $tag
     * @return $this
     * @author 陈妙威
     */
    public function setHeaderTag(PHUITagView $tag)
    {
        $this->headerTag = $tag;
        return $this;
    }

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array(
            'class' => 'phui-workpanel-view',
        );
    }

    /**
     * @return array|PHUIBoxView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//        require_celerity_resource('phui-workpanel-view-css');

        $footer = '';
        if ($this->footerAction) {
            $footer_tag = $this->footerAction;
            $footer = JavelinHtml::phutil_tag(
                'ul',
                array(
                    'class' => 'phui-workpanel-footer-action mst ps',
                ),
                $footer_tag);
        }

        $header = (new PHUIHeaderView())
            ->setHeader($this->header)
            ->setSubheader($this->subheader);

        foreach ($this->headerActions as $action) {
            $header->addActionItem($action);
        }

        if ($this->headerTag) {
            $header->addActionItem($this->headerTag);
        }

        if ($this->headerIcon) {
            $header->setHeaderIcon($this->headerIcon);
        }

        $href = $this->getHref();
        if ($href !== null) {
            $header->setHref($href);
        }

        $body = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-workpanel-body-content',
            ),
            $this->cards);

        $body = JavelinHtml::phutil_tag_div('phui-workpanel-body', $body);

        $view = (new PHUIBoxView())
            ->setColor(PHUIBoxView::BACKGROUND_GREY)
            ->addClass('phui-workpanel-view-inner')
            ->appendChild(
                array(
                    $header,
                    $body,
                    $footer,
                ));

        return $view;
    }
}
