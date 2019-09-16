<?php

namespace orangins\modules\transactions\response;

use orangins\lib\response\AphrontProxyResponse;

/**
 * Class PhabricatorApplicationTransactionValidationResponse
 * @package orangins\modules\transactions\response
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionValidationResponse extends AphrontProxyResponse
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $exception;
    /**
     * @var
     */
    private $cancelURI;

    /**
     * @param $cancel_uri
     * @return $this
     * @author 陈妙威
     */
    public function setCancelURI($cancel_uri)
    {
        $this->cancelURI = $cancel_uri;
        return $this;
    }

    /**
     * @param PhabricatorApplicationTransactionValidationException $exception
     * @return $this
     * @author 陈妙威
     */
    public function setException(
        PhabricatorApplicationTransactionValidationException $exception)
    {
        $this->exception = $exception;
        return $this;
    }

    /**
     * @return AphrontDialogResponse|mixed
     * @author 陈妙威
     */
    protected function buildProxy()
    {
        return new AphrontDialogResponse();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function reduceProxyResponse()
    {
        $request = $this->getRequest();

        $ex = $this->exception;
        $title = \Yii::t("app",'Validation Errors');

        $dialog = (new AphrontDialogView())
            ->setUser($request->getViewer())
            ->setTitle($title);

        $list = array();
        foreach ($ex->getErrors() as $error) {
            $list[] = $error->getMessage();
        }

        $dialog->appendList($list);
        $dialog->addCancelButton($this->cancelURI);

        return $this->getProxy()->setDialog($dialog);
    }

}
