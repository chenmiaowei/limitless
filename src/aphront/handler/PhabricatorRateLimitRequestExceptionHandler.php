<?php

namespace orangins\aphront\handler;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\system\exception\PhabricatorSystemActionRateLimitException;

/**
 * Class PhabricatorRateLimitRequestExceptionHandler
 * @package orangins\aphront\handler
 * @author 陈妙威
 */
final class PhabricatorRateLimitRequestExceptionHandler
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
        return pht(
            'Handles action rate limiting exceptions which occur when a user ' .
            'does something too frequently.');
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

        if (!$this->isPhabricatorSite($request)) {
            return false;
        }

        return ($throwable instanceof PhabricatorSystemActionRateLimitException);
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return mixed|AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function handleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {

        $viewer = $this->getViewer($request);

        return (new AphrontDialogView())
            ->setTitle(pht('Slow Down!'))
            ->setUser($viewer)
            ->setErrors(array(pht('You are being rate limited.')))
            ->appendParagraph($throwable->getMessage())
            ->appendParagraph($throwable->getRateExplanation())
            ->addCancelButton('/', pht('Okaaaaaaaaaaaaaay...'));
    }

}
