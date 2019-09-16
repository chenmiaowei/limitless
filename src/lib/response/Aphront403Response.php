<?php

namespace orangins\lib\response;

use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\page\PhabricatorStandardPageView;

/**
 * 403 Forbidden 禁止访问
 * Class Aphront403Response
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class Aphront403Response extends AphrontHTMLResponse
{

    /**
     * @var
     */
    private $forbiddenText;

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setForbiddenText($text)
    {
        $this->forbiddenText = $text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getForbiddenText()
    {
        return $this->forbiddenText;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        return 403;
    }

    /**
     * @author 陈妙威
     * @throws \Exception
     */
    public function buildResponseString()
    {
        $forbidden_text = $this->getForbiddenText();
        if (!$forbidden_text) {
            $forbidden_text =
                \Yii::t("app",'You do not have privileges to access the requested page.');
        }

        $request = $this->getRequest();
        $user = $request->getViewer();

        $dialog = (new AphrontDialogView())
            ->setUser($user)
            ->setTitle(\Yii::t("app",'403 Forbidden'))
            ->addCancelButton('/', \Yii::t("app",'Peace Out'))
            ->appendParagraph($forbidden_text);

        $view = (new PhabricatorStandardPageView())
            ->setTitle(\Yii::t("app",'403 Forbidden'))
            ->setRequest($request)
            ->setDeviceReady(true)
            ->appendChild($dialog);

        return $view->render();
    }

}
