<?php

namespace orangins\lib\response;

use orangins\modules\celerity\CelerityAPI;
use Yii;
use yii\helpers\Json;
use yii\web\Response;

/**
 * Class AphrontAjaxResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */

final class AphrontAjaxResponse extends AphrontResponse {

    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $error;
    /**
     * @var
     */
    private $disableConsole;

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * @param $error
     * @return $this
     * @author 陈妙威
     */
    public function setError($error) {
        $this->error = $error;
        return $this;
    }

    /**
     * @param $disable
     * @return $this
     * @author 陈妙威
     */
    public function setDisableConsole($disable) {
        $this->disableConsole = $disable;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    private function getConsole() {
        if ($this->disableConsole) {
            $console = null;
        } else {
            $request = $this->getRequest();
            $console = $request->getApplicationConfiguration()->getConsole();
        }
        return $console;
    }

    /**
     * @return string|void
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildResponseString() {
//        $console = $this->getConsole();
//        if ($console) {
//            // NOTE: We're stripping query parameters here both for readability and
//            // to mitigate BREACH and similar attacks. The parameters are available
//            // in the "Request" tab, so this should not impact usability. See T3684.
//            $uri = $this->getRequest()->getRequestURI();
//            $uri = new PhutilURI($uri);
//            $uri->setQueryParams(array());
//
//            Javelin::initBehavior(
//                'dark-console',
//                array(
//                    'uri'       => (string)$uri,
//                    'key'       => $console->getKey($this->getRequest()),
//                    'color'     => $console->getColor(),
//                    'quicksand' => $this->getRequest()->isQuicksand(),
//                ));
//        }

        // Flatten the response first, so we initialize any behaviors and metadata
        // we need to.
        $content = array(
            'payload' => $this->content,
        );
        $this->encodeJSONForHTTPResponse($content);

        $response = CelerityAPI::getStaticResourceResponse();

//        $request = $this->getRequest();
//        if ($request) {
//            $viewer = $request->getViewer();
//            if ($viewer) {
//                $postprocessor_key = $viewer->getUserSetting(
//                    PhabricatorAccessibilitySetting::SETTINGKEY);
//                if (strlen($postprocessor_key)) {
//                    $response->setPostprocessorKey($postprocessor_key);
//                }
//            }
//        }

        $object = $response->buildAjaxResponse(
            $content['payload'],
            $this->error);

        $response_json = $this->encodeJSONForHTTPResponse($object);
        return $this->addJSONShield($response_json);
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getHeaders() {
        $headers = array(
            array('Content-Type', 'text/plain; charset=UTF-8'),
        );
        $headers = array_merge(parent::getHeaders(), $headers);
        return $headers;
    }

}
