<?php

namespace orangins\modules\search\view;

use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUICrumbView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectItemListView;

/**
 * Holds bits and pieces of UI information for Search Engine
 * and Dashboard Panel rendering, describing the results and
 * controls for presentation.
 */
final class PhabricatorApplicationSearchResultView extends OranginsObject
{

    /**
     * @var PHUIObjectItemListView
     */
    private $objectList = null;
    /**
     * @var null
     */
    private $table = null;
    /**
     * @var null
     */
    private $content = null;
    /**
     * @var null
     */
    private $infoView = null;
    /**
     * @var array
     */
    private $actions = array();
    /**
     * @var
     */
    private $noDataString;
    /**
     * @var array
     */
    private $crumbs = array();
    /**
     * @var
     */
    private $header;

    /**
     * @var
     */
    private $footer;
    /**
     * @param PHUIObjectItemListView $list
     * @return $this
     * @author 陈妙威
     */
    public function setObjectList(PHUIObjectItemListView $list)
    {
        $this->objectList = $list;
        return $this;
    }

    /**
     * @return PHUIObjectItemListView
     * @author 陈妙威
     */
    public function getObjectList()
    {
        $list = $this->objectList;
        if ($list) {
            if ($this->noDataString) {
                $list->setNoDataString($this->noDataString);
            } else {
                $list->setNoDataString(\Yii::t("app",'No results found for this query.'));
            }
        }
        return $list;
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
     * @return null
     * @author 陈妙威
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param PHUIInfoView $infoview
     * @return $this
     * @author 陈妙威
     */
    public function setInfoView(PHUIInfoView $infoview)
    {
        $this->infoView = $infoview;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getInfoView()
    {
        return $this->infoView;
    }

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param PHUIButtonView $button
     * @return $this
     * @author 陈妙威
     */
    public function addAction(PHUIButtonView $button)
    {
        $this->actions[] = $button;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param $nodata
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($nodata)
    {
        $this->noDataString = $nodata;
        return $this;
    }

    /**
     * @param array $crumbs
     * @return $this
     * @author 陈妙威
     */
    public function setCrumbs(array $crumbs)
    {
        assert_instances_of($crumbs, PHUICrumbView::class);

        $this->crumbs = $crumbs;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCrumbs()
    {
        return $this->crumbs;
    }

    /**
     * @param PHUIHeaderView $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader(PHUIHeaderView $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return mixed
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * @param mixed $footer
     * @return self
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;
        return $this;
    }
}
