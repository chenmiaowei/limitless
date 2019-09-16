<?php

namespace orangins\aphront\handler;

use AphrontSchemaQueryException;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\AphrontMalformedRequestException;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;

/**
 * Class PhabricatorDefaultRequestExceptionHandler
 * @package orangins\aphront\handler
 * @author 陈妙威
 */
final class PhabricatorDefaultRequestExceptionHandler
    extends PhabricatorRequestExceptionHandler
{

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerPriority()
    {
        return 900000;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerDescription()
    {
        return pht('Handles all other exceptions.');
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

        return true;
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

        // Some types of uninteresting request exceptions don't get logged, usually
        // because they are caused by the background radiation of bot traffic on
        // the internet. These include requests with bad CSRF tokens and
        // questionable "Host" headers.
        $should_log = true;
        if ($throwable instanceof AphrontMalformedRequestException) {
            $should_log = !$throwable->getIsUnlogged();
        }

        if ($should_log) {
            phlog($throwable);
        }

        $class = get_class($throwable);
        $message = $throwable->getMessage();

        if ($throwable instanceof AphrontSchemaQueryException) {
            $message .= "\n\n" . pht(
                    "NOTE: This usually indicates that the MySQL schema has not been " .
                    "properly upgraded. Run '%s' to ensure your schema is up to date.",
                    'bin/storage upgrade');
        }

        if (PhabricatorEnv::getEnvConfig('phabricator.developer-mode')) {
            $trace = (new AphrontStackTraceView())
                ->setUser($viewer)
                ->setTrace($throwable->getTrace());
        } else {
            $trace = null;
        }

        $content = phutil_tag(
            'div',
            array('class' => 'aphront-unhandled-exception'),
            array(
                phutil_tag('div', array('class' => 'exception-message'), $message),
                $trace,
            ));

        $dialog = new AphrontDialogView();
        $dialog
            ->setTitle(pht('Unhandled Exception ("%s")', $class))
            ->setClass('aphront-exception-dialog')
            ->setUser($viewer)
            ->appendChild($content);

        if ($request->isAjax()) {
            $dialog->addCancelButton('/', pht('Close'));
        }

        return (new AphrontDialogResponse())
            ->setDialog($dialog)
            ->setHTTPResponseCode(500);
    }

}
