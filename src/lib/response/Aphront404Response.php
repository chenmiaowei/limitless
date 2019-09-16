<?php

namespace orangins\lib\response;

use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\page\PhabricatorStandardPageView;

/**
 * HTTP 404或Not Found 页面未找到
 * Class Aphront404Response
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class Aphront404Response extends AphrontHTMLResponse
{

    /**
     * @var
     */
    public $title;

    /**
     * @author 陈妙威
     */
    public function init()
    {
        $this->setTitle(\Yii::t("app",'404 Not Found'));
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }


    /**
     * @return int
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        return 404;
    }

    /**
     * @author 陈妙威
     * @throws \Exception
     */
    public function buildResponseString()
    {
        $request = $this->getRequest();
        $user = $request->getViewer();

        $dialog = (new AphrontDialogView())
            ->setViewer($user)
            ->setTitle($this->getTitle())
            ->addCancelButton(\Yii::$app->getHomeUrl(), \Yii::t("app",'Focus'))
            ->appendParagraph(
                \Yii::t("app",
                    'Do not dwell in the past, do not dream of the future, ' .
                    'concentrate the mind on the present moment.'));
        return $dialog;

//        $view = (new PhabricatorStandardPageView())
//            ->setTitle(\Yii::t("app",'404 Not Found'))
//            ->setRequest($request)
//            ->setDeviceReady(true)
//            ->appendChild($dialog);
//
//        return $view->render();
    }
}
