<?php

namespace orangins\modules\policy\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\phui\PHUIIconView;

/**
 * Class PHUIPolicySectionView
 * @package orangins\modules\policy\view
 * @author 陈妙威
 */
final class PHUIPolicySectionView
    extends AphrontTagView
{

    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $header;
    /**
     * @var
     */
    private $documentationLink;

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
     * @return mixed
     * @author 陈妙威
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param $name
     * @param $href
     * @return $this
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function setDocumentationLink($name, $href)
    {
        
        $link = JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => $href,
                'target' => '_blank',
            ),
            $name);

        $this->documentationLink = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-policy-section-view-link',
            ),
            array(
                (new PHUIIconView())->addClass("mr-2")->setIcon('fa-book'),
                $link,
            ));

        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDocumentationLink()
    {
        return $this->documentationLink;
    }

    /**
     * @param array $items
     * @return PHUIPolicySectionView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function appendList(array $items)
    {
        foreach ($items as $key => $item) {
            $items[$key] = JavelinHtml::phutil_tag(
                'li',
                array(
                    'class' => 'remarkup-list-item',
                ),
                $item);
        }

        $list = JavelinHtml::phutil_tag(
            'ul',
            array(
                'class' => 'remarkup-list',
            ),
            $items);

        return $this->appendChild($list);
    }

    /**
     * @param $content
     * @return PHUIPolicySectionView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function appendHint($content)
    {
        $hint = JavelinHtml::phutil_tag(
            'p',
            array(
                'class' => 'phui-policy-section-view-hint',
            ),
            array(
                (new PHUIIconView())
                    ->addClass("mr-2")
                    ->setIcon('fa-sticky-note bluegrey'),
                ' ',
                \Yii::t("app",'Note:'),
                ' ',
                $content,
            ));

        return $this->appendChild($hint);
    }

    /**
     * @param $content
     * @return PHUIPolicySectionView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function appendParagraph($content)
    {
        return $this->appendChild(JavelinHtml::phutil_tag('p', array(), $content));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array(
            'class' => 'phui-policy-section-view',
        );
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $icon_view = null;
        $icon = $this->getIcon();
        if ($icon !== null) {
            $icon_view = (new PHUIIconView())
                ->addClass("mr-2")
                ->setIcon($icon);
        }

        $header_view = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'mb-2 phui-policy-section-view-header-text',
            ),
            $this->getHeader());

        $header = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'mb-2 phui-policy-section-view-header',
            ),
            array(
                $icon_view,
                $header_view,
                $this->getDocumentationLink(),
            ));

        return array(
            $header,
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-policy-section-view-body',
                ),
                $this->renderChildren()),
        );
    }
}
