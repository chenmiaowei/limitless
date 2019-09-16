<?php

namespace orangins\modules\transactions\response;

use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontProxyResponse;

/**
 * Class PhabricatorApplicationTransactionWarningResponse
 * @package orangins\modules\transactions\response
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionWarningResponse extends AphrontProxyResponse
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
     * @param PhabricatorApplicationTransactionWarningException $exception
     * @return $this
     * @author 陈妙威
     */
    public function setException(
        PhabricatorApplicationTransactionWarningException $exception)
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

        $title = \Yii::t("app",'Warning: Unexpected Effects');

        $head = \Yii::t("app",
            'This is a draft revision that will not publish any notifications ' .
            'until the author requests review.');
        $tail = \Yii::t("app",
            'Mentioned or subscribed users will not be notified.');

        $continue = \Yii::t("app",'Tell No One');

        $dialog = (new AphrontDialogView())
            ->setViewer($request->getViewer())
            ->setTitle($title);

        $dialog->appendParagraph($head);
        $dialog->appendParagraph($tail);

        $passthrough = $request->getPassthroughRequestParameters();
        foreach ($passthrough as $key => $value) {
            $dialog->addHiddenInput($key, $value);
        }

        $dialog
            ->addHiddenInput('editEngine.warnings', 1)
            ->addSubmitButton($continue)
            ->addCancelButton($this->cancelURI);

        return $this->getProxy()->setDialog($dialog);
    }

}
