<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class AphrontMoreView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class AphrontMoreView extends AphrontView
{

    /**
     * @var
     */
    private $some;
    /**
     * @var
     */
    private $more;
    /**
     * @var
     */
    private $expandtext;

    /**
     * @param $some
     * @return $this
     * @author 陈妙威
     */
    public function setSome($some)
    {
        $this->some = $some;
        return $this;
    }

    /**
     * @param $more
     * @return $this
     * @author 陈妙威
     */
    public function setMore($more)
    {
        $this->more = $more;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setExpandText($text)
    {
        $this->expandtext = $text;
        return $this;
    }

    /**
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {

        $content = array();
        $content[] = $this->some;

        if ($this->more && $this->more != $this->some) {
            $text = "(" . \Yii::t("app", 'Show More') . "\xE2\x80\xA6)";
            if ($this->expandtext !== null) {
                $text = $this->expandtext;
            }

            Javelin::initBehavior('aphront-more');
            $content[] = ' ';
            $content[] = JavelinHtml::phutil_tag(
                'a',
                array(
                    'sigil' => 'aphront-more-view-show-more',
                    'mustcapture' => true,
                    'href' => '#',
                    'meta' => array(
                        'more' => $this->more,
                    ),
                ),
                $text);
        }

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'sigil' => 'aphront-more-view',
            ),
            $content);
    }
}
