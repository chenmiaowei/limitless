<?php

namespace orangins\lib\markup\rule;

use orangins\modules\celerity\CelerityAPI;
use PhutilRemarkupRule;
use PhutilURI;
use Exception;

/**
 * Class PhabricatorYoutubeRemarkupRule
 * @package orangins\lib\markup\rule
 * @author 陈妙威
 */
final class PhabricatorYoutubeRemarkupRule extends PhutilRemarkupRule
{

    /**
     * @return float
     * @author 陈妙威
     */
    public function getPriority()
    {
        return 350.0;
    }

    /**
     * @param $text
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function apply($text)
    {
        try {
            $uri = new PhutilURI($text);
        } catch (Exception $ex) {
            return $text;
        }

        $domain = $uri->getDomain();
        if (!preg_match('/(^|\.)youtube\.com\z/', $domain)) {
            return $text;
        }

        $params = $uri->getQueryParams();
        $v_param = ArrayHelper::getValue($params, 'v');
        if (!strlen($v_param)) {
            return $text;
        }

        $text_mode = $this->getEngine()->isTextMode();
        $mail_mode = $this->getEngine()->isHTMLMailMode();

        if ($text_mode || $mail_mode) {
            return $text;
        }

        $youtube_src = 'https://www.youtube.com/embed/' . $v_param;

        $iframe = $this->newTag(
            'div',
            array(
                'class' => 'embedded-youtube-video',
            ),
            $this->newTag(
                'iframe',
                array(
                    'width' => '650',
                    'height' => '400',
                    'style' => 'margin: 1em auto; border: 0px;',
                    'src' => $youtube_src,
                    'frameborder' => 0,
                ),
                ''));

        return $this->getEngine()->storeText($iframe);
    }

    /**
     * @author 陈妙威
     */
    public function didMarkupText()
    {
        CelerityAPI::getStaticResourceResponse()
            ->addContentSecurityPolicyURI('frame-src', 'https://www.youtube.com/');
    }

}
