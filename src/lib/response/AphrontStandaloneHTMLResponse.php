<?php

namespace orangins\lib\response;

use orangins\lib\helpers\JavelinHtml;

/**
 * Class AphrontStandaloneHTMLResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
abstract class AphrontStandaloneHTMLResponse
    extends AphrontHTMLResponse
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getResources();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getResponseTitle();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getResponseBodyClass();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getResponseBody();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function buildPlainTextResponseString();

    /**
     * @return string|void
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    final public function buildResponseString()
    {
        // Check to make sure we aren't requesting this via Ajax or Conduit.
        if (\Yii::$app->request->isAjax() || isset($_REQUEST['__conduit__'])) {
            return (string)JavelinHtml::hsprintf('%s', $this->buildPlainTextResponseString());
        }

        $title = $this->getResponseTitle();
        $resources = $this->buildResources();
        $body_class = $this->getResponseBodyClass();
        $body = $this->getResponseBody();

        return (string)JavelinHtml::hsprintf(
            <<<EOTEMPLATE
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>%s</title>
    %s
  </head>
  %s
</html>
EOTEMPLATE
            ,
            $title,
            $resources,
            JavelinHtml::phutil_tag(
                'body',
                array(
                    'class' => $body_class,
                ),
                $body));
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private function buildResources()
    {
        $paths = $this->getResources();

        $webroot = dirname(phutil_get_library_root('orangins')) . '/webroot/';

        $resources = array();
        foreach ($paths as $path) {
            $resources[] = JavelinHtml::phutil_tag(
                'style',
                array('type' => 'text/css'),
                phutil_safe_html(Filesystem::readFile($webroot . '/rsrc/' . $path)));
        }

        return phutil_implode_html("\n", $resources);
    }


}
