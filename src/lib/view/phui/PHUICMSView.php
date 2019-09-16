<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUICMSView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUICMSView extends AphrontTagView
{

    /**
     * @var
     */
    private $header;
    /**
     * @var
     */
    private $nav;
    /**
     * @var
     */
    private $crumbs;
    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $toc;
    /**
     * @var
     */
    private $comments;

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
     * @param AphrontSideNavFilterView $nav
     * @return $this
     * @author 陈妙威
     */
    public function setNavigation(AphrontSideNavFilterView $nav)
    {
        $this->nav = $nav;
        return $this;
    }

    /**
     * @param PHUICrumbsView $crumbs
     * @return $this
     * @author 陈妙威
     */
    public function setCrumbs(PHUICrumbsView $crumbs)
    {
        $this->crumbs = $crumbs;
        return $this;
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
     * @param $toc
     * @return $this
     * @author 陈妙威
     */
    public function setToc($toc)
    {
        $this->toc = $toc;
        return $this;
    }

    /**
     * @param $comments
     * @author 陈妙威
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'div';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//        require_celerity_resource('phui-cms-css');

        $classes = array();
        $classes[] = 'phui-cms-view';

        if ($this->comments) {
            $classes[] = 'phui-cms-has-comments';
        }

        return array(
            'class' => implode(' ', $classes),
        );

    }

    /**
     * @return array|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        $content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-cms-page-content',
            ),
            array(
                $this->header,
                $this->content,
            ));

        $comments = null;
        if ($this->comments) {
            $comments = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-cms-comments',
                ),
                array(
                    $this->comments,
                ));
        }

        $navigation = $this->nav;
        $navigation->appendChild($content);
        $navigation->appendChild($comments);

        $page = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-cms-inner',
            ),
            array(
                $navigation,
            ));

        $cms_view = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-cms-wrap',
            ),
            array(
                $this->crumbs,
                $page,
            ));

        $classes = array();
        $classes[] = 'phui-cms-page';

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
            ),
            $cms_view);
    }
}
