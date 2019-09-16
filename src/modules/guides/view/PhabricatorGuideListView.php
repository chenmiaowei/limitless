<?php

namespace orangins\modules\guides\view;

use orangins\lib\view\AphrontView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;

/**
 * Class PhabricatorGuideListView
 * @package orangins\modules\guides\view
 * @author 陈妙威
 */
final class PhabricatorGuideListView extends AphrontView
{

    /**
     * @var array
     */
    private $items = array();

    /**
     * @param PhabricatorGuideItemView $item
     * @return $this
     * @author 陈妙威
     */
    public function addItem(PhabricatorGuideItemView $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function render()
    {
        $list = (new PHUIObjectItemListView())
            ->setBig(true);

        foreach ($this->items as $item) {
            $icon = (new PHUIIconView())
                ->setIcon($item->getIcon())
                ->setBackground($item->getIconBackground());

            $list_item = (new PHUIObjectItemView())
                ->setHeader($item->getTitle())
                ->setHref($item->getHref())
                ->setImageIcon($icon)
                ->addAttribute($item->getDescription());

            $skip_href = $item->getSkipHref();
            if ($skip_href) {
                $skip = (new PHUIButtonView())
                    ->setText(\Yii::t("app", 'Skip'))
                    ->setTag('a')
                    ->setHref($skip_href)
                    ->setButtonType(PHUIButtonView::BUTTONTYPE_SIMPLE);
                $list_item->setSideColumn($skip);
            }
            $list->addItem($list_item);
        }

        return $list;
    }
}
