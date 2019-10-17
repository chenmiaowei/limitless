<?php

namespace orangins\lib\view\page;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use PhutilSafeHTML;
use orangins\lib\view\AphrontView;
use orangins\modules\widgets\assets\AppAsset;
use Yii;
use yii\web\View;

/**
 * Class AphrontPageView
 * @package orangins\lib\view\page
 * @author 陈妙威
 */
abstract class AphrontPageView extends AphrontView
{
    /**
     * @var
     */
    private $title;

    /**
     * @var View
     */
    private $view;

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitle()
    {
        $title = $this->title;
        if (is_array($title)) {
            $title = implode(" \xC2\xB7 ", $title);
        }
        return $title;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getHead()
    {
        return '';
    }

    /**
     * @return PhutilSafeHTML
     * @throws Exception
     * @author 陈妙威
     */
    protected function getBody()
    {
        return JavelinHtml::phutil_implode_html('', $this->renderChildren());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTail()
    {
        return '';
    }

    /**
     * @author 陈妙威
     */
    protected function willRenderPage()
    {
        return;
    }

    /**
     * @param $response
     * @return mixed
     * @author 陈妙威
     */
    protected function willSendResponse($response)
    {
        return $response;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getBodyClasses()
    {
        return null;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {

        $this->willRenderPage();

        $title = $this->getTitle();
        $head = $this->getHead();
        $body = $this->getBody();
        $tail = $this->getTail();

        $body_classes = $this->getBodyClasses();
        if (PhabricatorEnv::getEnvConfig('phabricator.developer-mode')) {
            $data_fragment = JavelinHtml::phutil_safe_html(' data-developer-mode="1"');
        } else {
            $data_fragment = null;
        }
        $view = $this->getView();
        if(!$view) {
            $view = Yii::$app->getView();
        }
        AppAsset::register($view);

        ob_start();
        ob_implicit_flush(false);
        $view->beginPage();
        echo '<!DOCTYPE html>';
        echo JavelinHtml::hsprintf('<html%s>', $data_fragment);
        echo JavelinHtml::beginTag('head');
        echo '<meta charset="' . Yii::$app->charset . '">';
        echo JavelinHtml::hsprintf('<title>%s</title>', $title);
        echo new PhutilSafeHTML(JavelinHtml::csrfMetaTags());
        echo $head;
        $view->head();
        echo JavelinHtml::endTag('head');
        echo JavelinHtml::beginTag('body', array(
            'class' => OranginsUtil:: nonempty($body_classes, null),
        ));
        $view->beginBody();
        echo JavelinHtml::hsprintf('%s', JavelinHtml::phutil_implode_html("\n", array($body, $tail)));
        $view->endBody();
        echo JavelinHtml::endTag('body');
        echo '</html>';
        $view->endPage();
        $response = ob_get_clean();

        $response = $this->willSendResponse($response);

        return $response;

    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param View $view
     * @return static
     */
    public function setView($view)
    {
        $this->view = $view;
        return $this;
    }
}
