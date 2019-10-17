<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use Yii;

/**
 * Class PHUIDocumentSummaryView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIDocumentSummaryView extends AphrontTagView
{

    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $image;
    /**
     * @var
     */
    private $imageHref;
    /**
     * @var
     */
    private $subtitle;
    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $summary;
    /**
     * @var
     */
    private $draft;

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
     * @param $subtitle
     * @return $this
     * @author 陈妙威
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * @param $image
     * @return $this
     * @author 陈妙威
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @param $image_href
     * @return $this
     * @author 陈妙威
     */
    public function setImageHref($image_href)
    {
        $this->imageHref = $image_href;
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
     * @param $summary
     * @return $this
     * @author 陈妙威
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @param $draft
     * @return $this
     * @author 陈妙威
     */
    public function setDraft($draft)
    {
        $this->draft = $draft;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'phui-document-summary-view';
        $classes[] = 'phabricator-remarkup';

        if ($this->draft) {
            $classes[] = 'is-draft';
        }

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//        require_celerity_resource('phui-document-summary-view-css');

        $title = JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => $this->href,
            ),
            $this->title);

        $header = JavelinHtml::phutil_tag(
            'h2',
            array(
                'class' => 'remarkup-header',
            ),
            $title);

        $subtitle = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-document-summary-subtitle',
            ),
            $this->subtitle);

        $body = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-document-summary-body',
            ),
            $this->summary);

        $read_more = JavelinHtml::phutil_tag(
            'a',
            array(
                'class' => 'phui-document-read-more',
                'href' => $this->href,
            ),
            Yii::t("app", 'Read more...'));

        return array($header, $subtitle, $body, $read_more);
    }

}
