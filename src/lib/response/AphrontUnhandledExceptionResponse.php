<?php

namespace orangins\lib\response;

use orangins\lib\exception\AphrontMalformedRequestException;
use Exception;
use yii\helpers\Html;

/**
 * Class AphrontUnhandledExceptionResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class AphrontUnhandledExceptionResponse
    extends AphrontStandaloneHTMLResponse
{

    /**
     * @var
     */
    private $exception;

    /**
     * @param Exception $exception
     * @return $this
     * @author 陈妙威
     */
    public function setException(Exception $exception)
    {
        // Log the exception unless it's specifically a silent malformed request
        // exception.

        $should_log = true;
        if ($exception instanceof AphrontMalformedRequestException) {
            if ($exception->getIsUnlogged()) {
                $should_log = false;
            }
        }

        if ($should_log) {
            \Yii::error($exception);
        }

        $this->exception = $exception;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        return 500;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getResources()
    {
        return array(
            'css/application/config/config-template.css',
            'css/application/config/unhandled-exception.css',
        );
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getResponseTitle()
    {
        $ex = $this->exception;

        if ($ex instanceof AphrontMalformedRequestException) {
            return $ex->getTitle();
        } else {
            return \Yii::t("app",'Unhandled Exception');
        }
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getResponseBodyClass()
    {
        return 'unhandled-exception';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getResponseBody()
    {
        $ex = $this->exception;

        if ($ex instanceof AphrontMalformedRequestException) {
            $title = $ex->getTitle();
        } else {
            $title = get_class($ex);
        }

        $body = $ex->getMessage();
        $body = phutil_escape_html_newlines($body);

        return Html::tag(
            'div',
            array(
                'class' => 'unhandled-exception-detail',
            ),
            array(
                Html::tag(
                    'h1',
                    array(
                        'class' => 'unhandled-exception-title',
                    ),
                    $title),
                Html::tag(
                    'div',
                    array(
                        'class' => 'unhandled-exception-body',
                    ),
                    $body),
            ));
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function buildPlainTextResponseString()
    {
        $ex = $this->exception;

        return \Yii::t("app",
            '{0}: {1}',
            [
                get_class($ex),
                $ex->getMessage()
            ]);
    }

}
