<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\response\AphrontResponse;
use PhutilSafeHTML;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\AphrontView;

/**
 * Class PHUIObjectItemListView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIObjectItemListView extends AphrontTagView
{

    /**
     * @var
     */
    private $header;
    /**
     * @var AphrontView[]
     */
    private $items;
    /**
     * @var
     */
    private $pager;
    /**
     * @var
     */
    private $noDataString;
    /**
     * @var
     */
    private $flush;
    /**
     * @var
     */
    private $simple;
    /**
     * @var
     */
    private $big;
    /**
     * @var
     */
    private $drag;
    /**
     * @var
     */
    private $allowEmptyList;
    /**
     * @var string
     */
    private $itemClass = 'phui-oi-standard';

    /**
     * @var array
     */
    private $tail = array();


    /**
     * @param $allow_empty_list
     * @return $this
     * @author 陈妙威
     */
    public function setAllowEmptyList($allow_empty_list)
    {
        $this->allowEmptyList = $allow_empty_list;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAllowEmptyList()
    {
        return $this->allowEmptyList;
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
     * @param $pager
     * @return $this
     * @author 陈妙威
     */
    public function setPager($pager)
    {
        $this->pager = $pager;
        return $this;
    }

    /**
     * @param $simple
     * @return $this
     * @author 陈妙威
     */
    public function setSimple($simple)
    {
        $this->simple = $simple;
        return $this;
    }

    /**
     * @param $big
     * @return $this
     * @author 陈妙威
     */
    public function setBig($big)
    {
        $this->big = $big;
        return $this;
    }

    /**
     * @param $drag
     * @return $this
     * @author 陈妙威
     */
    public function setDrag($drag)
    {
        $this->drag = $drag;
        $this->setItemClass('phui-oi-drag');
        return $this;
    }

    /**
     * @param $no_data_string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data_string)
    {
        $this->noDataString = new PhutilSafeHTML($no_data_string);
        return $this;
    }

    /**
     * @param PHUIObjectItemView $item
     * @return $this
     * @author 陈妙威
     */
    public function addItem(PHUIObjectItemView $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param $item_class
     * @return $this
     * @author 陈妙威
     */
    public function setItemClass($item_class)
    {
        $this->itemClass = $item_class;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'ul';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'media-list media-list-linked media-list-bordered phui-oi-list-view';

        if ($this->flush) {
            $classes[] = 'phui-oi-list-flush';
//            require_celerity_resource('phui-oi-flush-ui-css');
        }

        if ($this->simple) {
            $classes[] = 'phui-oi-list-simple';
//            require_celerity_resource('phui-oi-simple-ui-css');
        }

        if ($this->big) {
            $classes[] = 'phui-oi-list-big';
//            require_celerity_resource('phui-oi-big-ui-css');
        }

        if ($this->drag) {
            $classes[] = 'phui-oi-list-drag';
//            require_celerity_resource('phui-oi-drag-ui-css');
        }

        return array(
            'class' => $classes,
        );
    }

    /**
     * @return PHUIButtonView
     * @author 陈妙威
     */
    public function newTailButton() {
        $button = (new PHUIButtonView())
            ->setTag('a')
            ->setColor(PHUIButtonView::COLOR_GREY_800)
            ->setIcon('fa-chevron-down')
            ->setText(pht('View All Results'));

        $this->tail[] = $button;

        return $button;
    }

    /**
     * @return array|AphrontResponse|string
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $viewer = $this->hasViewer() ? $this->getViewer() : null;

        $header = null;
        if (strlen($this->header)) {
            $header = JavelinHtml::tag('h1', $this->header, array(
                'class' => 'm-0 pl-3 pt-1 pb-1 alpha-danger  phui-oi-list-header',
            ));
        }

        if ($this->items) {
            if ($viewer) {
                foreach ($this->items as $item) {
                    $item->setViewer($viewer);
                }
            }

            foreach ($this->items as $item) {
                $item->addClass($this->itemClass);
            }

            $items = $this->items;
        } else if ($this->allowEmptyList) {
            $items = null;
        } else {
            $string = OranginsUtil::nonempty($this->noDataString, \Yii::t("app", 'No data.'));
            $string = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NODATA)
                ->addClass("m-3")
                ->appendChild($string);
            $items = JavelinHtml::phutil_tag('li', array(
                'class' => 'phui-oi-empty',
            ), $string);

        }

        $pager = null;
        if ($this->pager) {
            $pager = $this->pager;
        }

        return array(
            $header,
            $items,
            $pager,
            $this->renderChildren(),
        );
    }

}
