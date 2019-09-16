<?php

namespace orangins\lib\view\control;

use orangins\lib\env\PhabricatorEnv;
use PhutilInvalidStateException;
use PhutilURI;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\AphrontView;

/**
 * Class AphrontCursorPagerView
 * @package orangins\lib\view\control
 * @author 陈妙威
 */
final class AphrontCursorPagerView extends AphrontView
{

    /**
     * @var
     */
    private $afterID;
    /**
     * @var
     */
    private $beforeID;

    /**
     * @var int
     */
    private $pageSize = 100;

    /**
     * @var
     */
    private $nextPageID;
    /**
     * @var
     */
    private $prevPageID;
    /**
     * @var
     */
    private $moreResults;

    /**
     * @var
     */
    private $uri;

    /**
     * @param $page_size
     * @return $this
     * @author 陈妙威
     */
    public function setPageSize($page_size) {
        $this->pageSize = max(1, $page_size);
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getPageSize() {
        return $this->pageSize;
    }

    /**
     * @param PhutilURI $uri
     * @return $this
     * @author 陈妙威
     */
    public function setURI(PhutilURI $uri) {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function readFromRequest(AphrontRequest $request) {
        $this->uri = $request->getRequestURI();
        $this->afterID = $request->getStr('after');
        $this->beforeID = $request->getStr('before');
        return $this;
    }

    /**
     * @param $after_id
     * @return $this
     * @author 陈妙威
     */
    public function setAfterID($after_id) {
        $this->afterID = $after_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAfterID() {
        return $this->afterID;
    }

    /**
     * @param $before_id
     * @return $this
     * @author 陈妙威
     */
    public function setBeforeID($before_id) {
        $this->beforeID = $before_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBeforeID() {
        return $this->beforeID;
    }

    /**
     * @param $next_page_id
     * @return $this
     * @author 陈妙威
     */
    public function setNextPageID($next_page_id) {
        $this->nextPageID = $next_page_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNextPageID() {
        return $this->nextPageID;
    }

    /**
     * @param $prev_page_id
     * @return $this
     * @author 陈妙威
     */
    public function setPrevPageID($prev_page_id) {
        $this->prevPageID = $prev_page_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPrevPageID() {
        return $this->prevPageID;
    }

    /**
     * @param array $results
     * @return array
     * @author 陈妙威
     */
    public function sliceResults(array $results) {
        if (count($results) > $this->getPageSize()) {
            $offset = ($this->beforeID ? count($results) - $this->getPageSize() : 0);
            $results = array_slice($results, $offset, $this->getPageSize(), true);
            $this->moreResults = true;
        }
        return $results;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHasMoreResults() {
        return $this->moreResults;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function willShowPagingControls() {
        return $this->prevPageID ||
            $this->nextPageID ||
            $this->afterID ||
            ($this->beforeID && $this->moreResults);
    }

    /**
     * @return null
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getFirstPageURI() {
        if (!$this->uri) {
            throw new PhutilInvalidStateException('setURI');
        }

        if (!$this->afterID && !($this->beforeID && $this->moreResults)) {
            return null;
        }

        $uri = clone $this->uri;
        return $uri
            ->removeQueryParam('after')
            ->removeQueryParam('before');
    }

    /**
     * @return null
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getPrevPageURI() {
        if (!$this->uri) {
            throw new PhutilInvalidStateException('getPrevPageURI');
        }

        if (!$this->prevPageID) {
            return null;
        }

        $x = clone $this->uri;
        return $x
            ->removeQueryParam('after')
            ->replaceQueryParam('before', $this->prevPageID);
    }

    /**
     * @return null
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getNextPageURI() {
        if (!$this->uri) {
            throw new PhutilInvalidStateException('setURI');
        }

        if (!$this->nextPageID) {
            return null;
        }

        $x = clone $this->uri;
        return $x
            ->replaceQueryParam('after', $this->nextPageID)
            ->removeQueryParam('before');
    }

    /**
     * @return mixed|\PhutilSafeHTML
     * @throws PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render() {
        if (!$this->uri) {
            throw new PhutilInvalidStateException('setURI');
        }

        $links = array();

        $first_uri = $this->getFirstPageURI();
        if ($first_uri) {
            $icon = (new PHUIIconView())
                ->addClass("mr-2")
                ->setIcon('fa-fast-backward');
            $links[] = (new PHUIButtonView())
                ->setTag('a')
                ->setHref($first_uri)
                ->setIcon($icon)
                ->addClass('mml')
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                ->setText(pht('First'));
        }

        $prev_uri = $this->getPrevPageURI();
        if ($prev_uri) {
            $icon = (new PHUIIconView())
                ->addClass("mr-2")
                ->setIcon('fa-backward');
            $links[] = (new PHUIButtonView())
                ->addClass("ml-2")
                ->setTag('a')
                ->setHref($prev_uri)
                ->setIcon($icon)
                ->addClass('mml')
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                ->setText(pht('Prev'));
        }

        $next_uri = $this->getNextPageURI();
        if ($next_uri) {
            $icon = (new PHUIIconView())
                ->addClass("ml-2")
                ->setIcon('fa-forward');
            $links[] = (new PHUIButtonView())
                ->addClass("ml-2")
                ->setTag('a')
                ->setHref($next_uri)
                ->setIcon($icon, false)
                ->addClass('mml')
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                ->setText(pht('Next'));
        }

        return phutil_tag(
            'div',
            array(
                'class' => 'text-center phui-pager-view',
            ),
            $links);
    }
}
