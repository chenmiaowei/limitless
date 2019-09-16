<?php

namespace orangins\lib\infrastructure\cluster\exception;

use orangins\aphront\handler\PhabricatorRequestExceptionHandler;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;

/**
 * Class PhabricatorClusterExceptionHandler
 * @package orangins\lib\infrastructure\cluster\exception
 * @author 陈妙威
 */
final class PhabricatorClusterExceptionHandler
    extends PhabricatorRequestExceptionHandler
{

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerPriority()
    {
        return 300000;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerDescription()
    {
        return pht('Handles runtime problems with cluster configuration.');
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return bool|mixed
     * @author 陈妙威
     */
    public function canHandleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {
        return ($throwable instanceof PhabricatorClusterException);
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return mixed|AphrontDialogResponse
     * @throws \Exception
     * @author 陈妙威
     */
    public function handleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {

        $viewer = $this->getViewer($request);

        $title = $throwable->getExceptionTitle();

        $dialog = (new AphrontDialogView())
            ->setTitle($title)
            ->setUser($viewer)
            ->appendParagraph($throwable->getMessage())
            ->addCancelButton('/', pht('Proceed With Caution'));

        return (new AphrontDialogResponse())
            ->setDialog($dialog)
            ->setHTTPResponseCode(500);
    }

}
