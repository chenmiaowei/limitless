<?php

namespace orangins\modules\phid\view;

use orangins\lib\view\AphrontView;
use orangins\modules\phid\handles\pool\PhabricatorHandleList;
use PhutilUTF8StringTruncator;

/**
 * Convenience class for rendering a single handle.
 *
 * This class simplifies rendering a single handle, and improves loading and
 * caching semantics in the rendering pipeline by loading data at the last
 * moment.
 */
final class PHUIHandleView
    extends AphrontView
{

    /**
     * @var
     */
    private $handleList;
    /**
     * @var
     */
    private $handlePHID;
    /**
     * @var
     */
    private $asTag;
    /**
     * @var
     */
    private $asText;
    /**
     * @var
     */
    private $useShortName;
    /**
     * @var
     */
    private $showHovercard;
    /**
     * @var
     */
    private $showStateIcon;
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
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setHandlePHID($phid)
    {
        $this->handlePHID = $phid;
        return $this;
    }

    /**
     * @param $tag
     * @return $this
     * @author 陈妙威
     */
    public function setAsTag($tag)
    {
        $this->asTag = $tag;
        return $this;
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
     * @param $short
     * @return $this
     * @author 陈妙威
     */
    public function setUseShortName($short)
    {
        $this->useShortName = $short;
        return $this;
    }

    /**
     * @param $hovercard
     * @return $this
     * @author 陈妙威
     */
    public function setShowHovercard($hovercard)
    {
        $this->showHovercard = $hovercard;
        return $this;
    }

    /**
     * @param $show_state_icon
     * @return $this
     * @author 陈妙威
     */
    public function setShowStateIcon($show_state_icon)
    {
        $this->showStateIcon = $show_state_icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getShowStateIcon()
    {
        return $this->showStateIcon;
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
     * @return array|mixed
     * @author 陈妙威
     */
    public function render()
    {
        $handle = $this->handleList[$this->handlePHID];

        if ($this->asTag) {
            $tag = $handle->renderTag();

            if ($this->showHovercard) {
                $tag->setPHID($handle->getPHID());
            }

            return $tag;
        }

        if ($this->asText) {
            return $handle->getLinkName();
        }

        if ($this->useShortName) {
            $name = $handle->getName();
        } else {
            $name = $handle->getLinkName();
        }

        $glyph_limit = $this->getGlyphLimit();
        if ($glyph_limit) {
            $name = (new PhutilUTF8StringTruncator())
                ->setMaximumGlyphs($glyph_limit)
                ->truncateString($name);
        }

        if ($this->showHovercard) {
            $link = $handle->renderHovercardLink($name);
        } else {
            $link = $handle->renderLink($name);
        }

        if ($this->showStateIcon) {
            $icon = $handle->renderStateIcon();
            $link = array($icon, ' ', $link);
        }

        return $link;
    }

}
