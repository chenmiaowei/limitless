<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use PhutilSortVector;

/**
 * Class PHUICurtainPanelView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class PHUICurtainPanelView extends AphrontTagView
{

    /**
     * @var int
     */
    private $order = 0;
    /**
     * @var
     */
    private $headerText;

    /**
     * @param $header_text
     * @return $this
     * @author 陈妙威
     */
    public function setHeaderText($header_text)
    {
        $this->headerText = $header_text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeaderText()
    {
        return $this->headerText;
    }

    /**
     * @param $order
     * @return $this
     * @author 陈妙威
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOrderVector()
    {
        return (new PhutilSortVector())
            ->addInt($this->getOrder());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array(
            'class' => 'm-3 phui-curtain-panel',
        );
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $header = null;

        $header_text = $this->getHeaderText();
        if (strlen($header_text)) {
            $header = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'font-size-lg pb-2 phui-curtain-panel-header',
                ),
                $header_text);
        }

        $body = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-curtain-panel-body',
            ),
            $this->renderChildren());

        return array(
            $header,
            $body,
        );
    }

}
