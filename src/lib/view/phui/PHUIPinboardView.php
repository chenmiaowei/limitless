<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class PHUIPinboardView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIPinboardView extends AphrontView
{

    /**
     * @var array
     */
    private $items = array();
    /**
     * @var
     */
    private $noDataString;

    /**
     * @param $no_data_string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data_string)
    {
        $this->noDataString = $no_data_string;
        return $this;
    }

    /**
     * @param PHUIPinboardItemView $item
     * @return $this
     * @author 陈妙威
     */
    public function addItem(PHUIPinboardItemView $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return array|mixed|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function render()
    {
//        require_celerity_resource('phui-pinboard-view-css');

        if (!$this->items) {
            $string = nonempty($this->noDataString, \Yii::t("app", 'No data.'));
            return (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NODATA)
                ->appendChild($string)
                ->render();
        }

        return JavelinHtml::phutil_tag(
            'ul',
            array(
                'class' => 'phui-pinboard-view',
            ),
            $this->items);
    }

}
