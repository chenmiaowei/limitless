<?php

namespace orangins\modules\phid\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\phid\handles\pool\PhabricatorHandleList;

/**
 * Convenience class for rendering a list of handles.
 *
 * This class simplifies rendering a list of handles and improves loading and
 * caching semantics in the rendering pipeline by delaying bulk loads until the
 * last possible moment.
 */
final class PHUIHandleListView
    extends AphrontTagView
{

    /**
     * @var
     */
    private $handleList;
    /**
     * @var
     */
    private $asInline;
    /**
     * @var
     */
    private $asText;
    /**
     * @var
     */
    private $showStateIcons;
    /**
     * @var
     */
    private $glyphLimit;

    /**
     * @param PhabricatorHandleList $list
     * @return $this
     * @author 陈妙威
     */
    public function setHandleList(PhabricatorHandleList $list)
    {
        $this->handleList = $list;
        return $this;
    }

    /**
     * @param $inline
     * @return $this
     * @author 陈妙威
     */
    public function setAsInline($inline)
    {
        $this->asInline = $inline;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAsInline()
    {
        return $this->asInline;
    }

    /**
     * @param $as_text
     * @return $this
     * @author 陈妙威
     */
    public function setAsText($as_text)
    {
        $this->asText = $as_text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAsText()
    {
        return $this->asText;
    }

    /**
     * @param $show_state_icons
     * @return $this
     * @author 陈妙威
     */
    public function setShowStateIcons($show_state_icons)
    {
        $this->showStateIcons = $show_state_icons;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getShowStateIcons()
    {
        return $this->showStateIcons;
    }

    /**
     * @param $glyph_limit
     * @return $this
     * @author 陈妙威
     */
    public function setGlyphLimit($glyph_limit)
    {
        $this->glyphLimit = $glyph_limit;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getGlyphLimit()
    {
        return $this->glyphLimit;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        if ($this->getAsText()) {
            return null;
        } else {
            // TODO: It would be nice to render this with a proper <ul />, at least
            // in block mode, but don't stir the waters up too much for now.
            return 'span';
        }
    }

    /**
     * @return array|\PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $list = $this->handleList;

        $show_state_icons = $this->getShowStateIcons();
        $glyph_limit = $this->getGlyphLimit();

        $items = array();
        foreach ($list as $handle) {
            $view = $list->renderHandle($handle->getPHID())
                ->setShowHovercard(true)
                ->setAsText($this->getAsText());

            if ($show_state_icons) {
                $view->setShowStateIcon(true);
            }

            if ($glyph_limit) {
                $view->setGlyphLimit($glyph_limit);
            }

            $items[] = $view;
        }

        if ($this->getAsInline()) {
            $items = JavelinHtml::phutil_implode_html(', ', $items);
        } else {
            $items = JavelinHtml::phutil_implode_html(JavelinHtml::phutil_tag('br'), $items);
        }

        return $items;
    }

}
