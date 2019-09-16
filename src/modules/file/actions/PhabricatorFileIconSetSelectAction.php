<?php

namespace orangins\modules\file\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\file\iconset\PhabricatorIconSet;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;

/**
 * Class PhabricatorFileIconSetSelectController
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileIconSetSelectAction extends PhabricatorFileAction
{

    /**
     * @return \orangins\lib\view\AphrontDialogView|AphrontResponse
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @thd-flex flex-wraps \yii\base\Exception
     * @thd-flex flex-wraps \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $key = $request->getURIData('key');

        $set = PhabricatorIconSet::getIconSetByKey($key);
        if (!$set) {
            return new Aphront404Response();
        }

        $v_icon = $request->getStr('icon');
        if ($request->isFormPost()) {
            $icon = $set->getIcon($v_icon);

            if ($icon) {
                $payload = array(
                    'value' => $icon->getKey(),
                    'display' => $set->renderIconForControl($icon),
                );

                return (new AphrontAjaxResponse())
                    ->setContent($payload);
            }
        }

        JavelinHtml::initBehavior(new JavelinTooltipAsset());

        $ii = 0;
        $buttons = array();
        $breakpoint = ceil(sqrt(count($set->getIcons())));
        foreach ($set->getIcons() as $icon) {
            $label = $icon->getLabel();

            $view = (new PHUIIconView())
                ->setIcon($icon->getIcon());

            $classes = array();
            $classes[] = 'btn btn-sm';

            $is_selected = ($icon->getKey() == $v_icon);

            if ($is_selected) {
                $classes[] = 'selected';
            }

            $is_disabled = $icon->getIsDisabled();
            if ($is_disabled && !$is_selected) {
                continue;
            }

            $aural = JavelinHtml::phutil_tag(
                'span',
                array(
                    'aural' => true,
                ),
                \Yii::t("app",'Choose "%s" Icon', $label));

            $buttons[] = JavelinHtml::phutil_tag(
                'button',
                array(
                    'class' => implode(' ', $classes),
                    'name' => 'icon',
                    'value' => $icon->getKey(),
                    'type' => 'submit',
                    'sigil' => 'has-tooltip',
                    'meta' => array(
                        'tip' => $label,
                    ),
                ),
                array(
                    $aural,
                    $view,
                ));

//            if ((++$ii % $breakpoint) == 0) {
//                $buttons[] = new PhutilSafeHTML("</div><div class='d-flex flex-wrap'>");
//            }
        }

        $buttons = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'd-flex flex-wrap',
            ),
            $buttons);

        $dialog_title = $set->getSelectIconTitleText();

        return $this->newDialog()
            ->setTitle($dialog_title)
            ->appendChild($buttons)
            ->addCancelButton('/');
    }

}
