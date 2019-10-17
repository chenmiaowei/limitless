<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 12:27 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\layout;


use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;

/**
 * Class PHUICardView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
class PHUICardView extends AphrontTagView
{
    /**
     * @var string
     */
    public $text;

    /**
     * @var string
     */
    public $imageUrl;

    /**
     * @var string
     */
    public $href;
    /**
     * @var PHUIIconView
     */
    public $icon;

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param string $href
     * @return self
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     * @return self
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @param mixed $imageUrl
     * @return self
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        if (!($icon instanceof PHUIIconView)) {
            $icon = (new PHUIIconView())
                ->setType(PHUIIconView::TYPE_ICOMOON)
                ->addClass(PHUI::PADDING_SMALL_RIGHT)
                ->setIcon($icon);
        }
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return [
            "class" => "card " . implode(" ", $this->getClasses())
        ];
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $image = $this->getImageUrl() ? JavelinHtml::phutil_tag("div", [
            "class" => "card-img-actions d-inline-block mb-3"
        ], [
            JavelinHtml::phutil_tag("img", [
                "src" => $this->getImageUrl(),
                "class" => "w-100"
            ])
        ]) : null;

        $color = PhabricatorEnv::getEnvConfig("ui.widget-color");
        $icon = $this->icon ? JavelinHtml::phutil_tag("div", [
            "class" => "card-img-actions d-inline-block mb-3"
        ], [
            $this->icon->addClass("icon-2x text-{$color} border-{$color} border-3 rounded-round p-3 mb-1")
        ]) : null;


        $button = $this->getText() ? (new PHUIButtonView())
            ->setTag('a')
            ->addClass("d-block")
            ->setHref($this->getHref())
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->setText($this->getText()) : null;

        $cardBody = JavelinHtml::phutil_tag("div", [
            "class" => 'card-body text-center card-img-top'
        ], [
            $image,
            $icon,
            $button,
        ]);
        return [
            $cardBody
        ];
    }
}