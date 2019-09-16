<?php

namespace orangins\lib\view\page;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\AphrontRequest;
use orangins\modules\celerity\CelerityAPI;
use orangins\modules\file\favicon\PhabricatorFaviconRef;
use orangins\modules\file\favicon\PhabricatorFaviconRefQuery;
use orangins\modules\settings\setting\PhabricatorAccessibilitySetting;
use yii\helpers\ArrayHelper;

/**
 * This is a bare HTML page view which has access to Phabricator page
 * infrastructure like Celerity, but no content or builtin static resources.
 * You basically get a valid HMTL5 document and an empty body tag.
 *
 * @concrete-extensible
 */
class PhabricatorBarePageView extends AphrontPageView
{

    /**
     * @var AphrontRequest
     */
    private $request;

    /**
     * @var PhabricatorAction
     */
    private $action;
    /**
     * @var
     */
    private $frameable;
    /**
     * @var
     */
    private $deviceReady;

    /**
     * @var
     */
    private $bodyContent;

    /**
     * @return PhabricatorAction
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param PhabricatorAction $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return $this
     * @author 陈妙威
     */
    public function setRequest(AphrontRequest $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return AphrontRequest
     * @author 陈妙威
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param $frameable
     * @return $this
     * @author 陈妙威
     */
    public function setFrameable($frameable)
    {
        $this->frameable = $frameable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFrameable()
    {
        return $this->frameable;
    }

    /**
     * @param $device_ready
     * @return $this
     * @author 陈妙威
     */
    public function setDeviceReady($device_ready)
    {
        $this->deviceReady = $device_ready;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDeviceReady()
    {
        return $this->deviceReady;
    }

    /**
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    protected function willRenderPage()
    {
        // We render this now to resolve static resources so they can appear in the
        // document head.
        $pieces = $this->renderChildren();
        $phutilSafeHTML = JavelinHtml::phutil_implode_html('', $pieces);
        $this->bodyContent = $phutilSafeHTML;
    }

    /**
     * @return string
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getHead()
    {
        $viewport_tag = null;
        if ($this->getDeviceReady()) {
            $viewport_tag = JavelinHtml::phutil_tag(
                'meta',
                array(
                    'name' => 'viewport',
                    'content' => 'width=device-width, ' .
                        'initial-scale=1, ' .
                        'user-scalable=no',
                ));
        }

        $referrer_tag = JavelinHtml::phutil_tag(
            'meta',
            array(
                'name' => 'referrer',
                'content' => 'no-referrer',
            ));


        $mask_icon = JavelinHtml::phutil_tag(
            'link',
            array(
                'rel' => 'mask-icon',
                'color' => '#3D4B67',
//                'href' => celerity_get_resource_uri('/rsrc/favicons/mask-icon.svg'),
            ));

        $favicon_links = $this->newFavicons();

        $response = CelerityAPI::getStaticResourceResponse();

        if ($this->getRequest()) {
            $viewer = $this->getRequest()->getViewer();
            if ($viewer) {
                $postprocessor_key = $viewer->getUserSetting(PhabricatorAccessibilitySetting::SETTINGKEY);
                if (strlen($postprocessor_key)) {
                    $response->setPostProcessorKey($postprocessor_key);
                }
            }
        }

//        return JavelinHtml::hsprintf(
//            '%s%s%s%s%s',
//            $viewport_tag,
//            $mask_icon,
//            $favicon_links,
//            $referrer_tag,
//            $response->renderResourcesOfType('css'));

        return JavelinHtml::hsprintf(
            '%s%s%s%s',
            $viewport_tag,
            $mask_icon,
            $favicon_links,
            $referrer_tag);


    }

    /**
     * @return \PhutilSafeHTML
     * @author 陈妙威
     */
    protected function getBody()
    {
        return $this->bodyContent;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTail()
    {
//        $response = CelerityAPI::getStaticResourceResponse();
//        return $response->renderResourcesOfType('js');
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function newFavicons()
    {
        $favicon_refs = array(
            array(
                'rel' => 'apple-touch-icon',
                'sizes' => '76x76',
                'width' => 76,
                'height' => 76,
            ),
            array(
                'rel' => 'apple-touch-icon',
                'sizes' => '120x120',
                'width' => 120,
                'height' => 120,
            ),
            array(
                'rel' => 'apple-touch-icon',
                'sizes' => '152x152',
                'width' => 152,
                'height' => 152,
            ),
            array(
                'rel' => 'icon',
                'id' => 'favicon',
                'width' => 64,
                'height' => 64,
            ),
        );

        $fetch_refs = array();
        foreach ($favicon_refs as $key => $spec) {
            $ref = (new PhabricatorFaviconRef())
                ->setWidth($spec['width'])
                ->setHeight($spec['height']);

            $favicon_refs[$key]['ref'] = $ref;
            $fetch_refs[] = $ref;
        }

        (new PhabricatorFaviconRefQuery())
            ->withRefs($fetch_refs)
            ->execute();

        $favicon_links = array();
        foreach ($favicon_refs as $spec) {
            /** @var PhabricatorFaviconRef $ref1 */
            $ref1 = $spec['ref'];
            $favicon_links[] = JavelinHtml::phutil_tag(
                'link',
                array(
                    'rel' => $spec['rel'],
                    'sizes' => ArrayHelper::getValue($spec, 'sizes'),
                    'id' => ArrayHelper::getValue($spec, 'id'),
                    'href' => $ref1->getURI(),
                ));
        }

        return $favicon_links;
    }

}
