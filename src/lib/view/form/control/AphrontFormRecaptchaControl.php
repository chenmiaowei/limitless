<?php

namespace orangins\lib\view\form\control;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\AphrontRequest;
use orangins\modules\celerity\CelerityAPI;

/**
 * Class AphrontFormRecaptchaControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormRecaptchaControl extends AphrontFormControl
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-recaptcha';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldRender()
    {
        return self::isRecaptchaEnabled();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function isRecaptchaEnabled()
    {
        return PhabricatorEnv::getEnvConfig('recaptcha.enabled');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    public static function hasCaptchaResponse(AphrontRequest $request)
    {
        return $request->getBool('g-recaptcha-response');
    }

    /**
     * @param AphrontRequest $request
     * @return bool
     * @author 陈妙威
     */
    public static function processCaptcha(AphrontRequest $request)
    {
        if (!self::isRecaptchaEnabled()) {
            return true;
        }

        $uri = 'https://www.google.com/recaptcha/api/siteverify';
        $params = array(
            'secret' => PhabricatorEnv::getEnvConfig('recaptcha.private-key'),
            'response' => $request->getStr('g-recaptcha-response'),
            'remoteip' => $request->getRemoteAddress(),
        );

        list($body) = (new HTTPSFuture($uri, $params))
            ->setMethod('POST')
            ->resolvex();

        $json = phutil_json_decode($body);
        return (bool)ArrayHelper::getValue($json, 'success');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function renderInput()
    {
        $js = 'https://www.google.com/recaptcha/api.js';
        $pubkey = PhabricatorEnv::getEnvConfig('recaptcha.public-key');

        CelerityAPI::getStaticResourceResponse()
            ->addContentSecurityPolicyURI('script-src', $js)
            ->addContentSecurityPolicyURI('script-src', 'https://www.gstatic.com/')
            ->addContentSecurityPolicyURI('frame-src', 'https://www.google.com/');

        return array(
            JavelinHtml::phutil_tag('div', array(
                    'class' => 'g-recaptcha',
                    'data-sitekey' => $pubkey,
                )),
            JavelinHtml::phutil_tag('script', array(
                    'type' => 'text/javascript',
                    'src' => $js,
                )),
        );
    }
}
