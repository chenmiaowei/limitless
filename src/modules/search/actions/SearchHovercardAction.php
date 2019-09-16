<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 5:08 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\search\actions;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\response\OranginsResponseInterface;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\engineextension\PhabricatorHovercardEngineExtension;
use orangins\lib\view\phui\PHUIHovercardView;
use yii\helpers\ArrayHelper;

/**
 * Class SearchHovercardAction
 * @package orangins\modules\search\actions
 * @author 陈妙威
 */
class SearchHovercardAction extends SearchAction
{
    /**
     * @return AphrontResponse|PhabricatorStandardPageView
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $viewer = $this->controller->getViewer();
        $request = $this->controller->getRequest();
        $phids = $request->getArr('phids');

        // If object names are provided, look them up and pretend they were
        // passed as additional PHIDs. This is primarily useful for debugging,
        // since you don't have to go look up user PHIDs to preview their
        // hovercards.
        $names = $request->getStrList('names');
        if ($names) {
            /** @var ActiveRecordPHID[] $named_objects */
            $named_objects = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withNames($names)
                ->execute();

            foreach ($named_objects as $object) {
                $phids[] = $object->getPHID();
            }
        }

        $handles = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs($phids)
            ->execute();

        $objects = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs($phids)
            ->execute();
        $objects = mpull($objects, null, 'getPHID');

        $extensions = PhabricatorHovercardEngineExtension::getAllEnabledExtensions();

        $extension_maps = array();
        foreach ($extensions as $key => $extension) {
            $extension->setViewer($viewer);

            $extension_phids = array();
            foreach ($objects as $phid => $object) {
                if ($extension->canRenderObjectHovercard($object)) {
                    $extension_phids[$phid] = $phid;
                }
            }

            $extension_maps[$key] = $extension_phids;
        }

        $extension_data = array();
        foreach ($extensions as $key => $extension) {
            $extension_phids = $extension_maps[$key];
            if (!$extension_phids) {
                unset($extensions[$key]);
                continue;
            }

            $extension_data[$key] = $extension->willRenderHovercards(array_select_keys($objects, $extension_phids));
        }

        /** @var OranginsResponseInterface[] $cards */
        $cards = array();
        foreach ($phids as $phid) {
            $handle = $handles[$phid];
            $object = ArrayHelper::getValue($objects, $phid);

            $hovercard = (new PHUIHovercardView())
                ->setViewer($viewer)
                ->setObjectHandle($handle);

            if ($object) {
                $hovercard->setObject($object);

                foreach ($extension_maps as $key => $extension_phids) {
                    if (isset($extension_phids[$phid])) {
                        $extensions[$key]->renderHovercard(
                            $hovercard,
                            $handle,
                            $object,
                            $extension_data[$key]);
                    }
                }
            }

            $cards[$phid] = $hovercard;
        }

        if ($request->isAjax()) {
            return (new AphrontAjaxResponse())->setContent(
                array(
                    'cards' => $cards,
                ));
        }

        foreach ($cards as $key => $hovercard) {
            $cards[$key] = JavelinHtml::tag('div', $hovercard, array(
                'class' => 'ml',
            ));
        }


        return $this->controller->newPage()
            ->appendChild(implode("\n", $cards))
            ->setShowFooter(false);
    }
}