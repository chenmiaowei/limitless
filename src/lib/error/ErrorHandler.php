<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/25
 * Time: 2:33 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\error;

use orangins\lib\response\AphrontPureJSONResponse;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\modules\people\models\PhabricatorUser;
use PhutilSafeHTML;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;
use Yii;
use yii\base\UserException;
use yii\helpers\Html;
use yii\web\Response;

/**
 * Class ErrorHandler
 * @package orangins\lib\error
 * @author 陈妙威
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @param \Error|\Exception $exception
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            // reset parameters of response to avoid interference with partially created response data
            // in case the error occurred while sending the response.
            $response->isSent = false;
            $response->stream = null;
            $response->data = null;
            $response->content = null;
        } else {
            $response = new Response();
        }

        if (Yii::$app->request->isAjax()) {
            $dialog = (new AphrontDialogView())
                ->setViewer(Yii::$app->user->identity)
                ->setTitle($exception->getMessage())
                ->appendChild(new PhutilSafeHTML(self::convertExceptionToString($exception)))
                ->addCancelButton('/');


//            $dialogBoxWidgetView = new AphrontDialogView();
//            $dialogBoxWidgetView->setTitle(get_class($exception));
//            $dialogBoxWidgetView->appendChild(Html::tag("pre", self::convertExceptionToString($exception)));
//            $dialogBoxWidgetView->addCancelButton();

            $dialogResponse = (new AphrontDialogResponse())->setDialog($dialog);
            $phutilSafeHTML = $dialogResponse->buildResponseString();
            $response->data = (new AphrontAjaxResponse())
                ->setContent(array(
                    'dialog' => $phutilSafeHTML,
                ))->buildResponseString();

        } else if (Yii::$app->request->getHeaders()->get("Accept") === "application/json") {
            $response->data = (new AphrontPureJSONResponse())
                ->setContent(array(
                    'error_code' => 'API-ERROR-SYSTEM',
                    'error_info' => $exception->getMessage(),
                    'trace' => (!YII_DEBUG || $exception instanceof UserException) ? null : self::convertExceptionToArray($exception),
                ))->buildResponseString();
        } else {
            $response->setStatusCodeByException($exception);
            $useErrorView = $response->format === Response::FORMAT_HTML && (!YII_DEBUG || $exception instanceof UserException);

            if ($useErrorView && $this->errorAction !== null) {
                $result = Yii::$app->runAction($this->errorAction);
                if ($result instanceof Response) {
                    $response = $result;
                } else {
                    $response->data = $result;
                }
            } elseif ($response->format === Response::FORMAT_HTML) {
                if ($this->shouldRenderSimpleHtml()) {
                    // AJAX request
                    $response->data = '<pre>' . $this->htmlEncode(static::convertExceptionToString($exception)) . '</pre>';
                } else {
                    // if there is an error during error rendering it's useful to
                    // display PHP error in debug mode instead of a blank screen
                    if (YII_DEBUG) {
                        ini_set('display_errors', 1);
                    }
                    $dialog = (new AphrontDialogView())
                        ->setViewer(PhabricatorUser::getOmnipotentUser())
                        ->setTitle($exception->getMessage())
                        ->appendChild(new PhutilSafeHTML(self::convertExceptionToString($exception)))
                        ->addCancelButton('/');

                    $phabricatorStandardPageView = new PhabricatorStandardPageView();
                    $phabricatorStandardPageView
                        ->appendChild($dialog)
                        ->setRequest(Yii::$app->request)
                        ->setViewer(PhabricatorUser::getOmnipotentUser())
                        ->setView(Yii::$app->view);


                    $response->data = $phabricatorStandardPageView->render();

//                    $file = $useErrorView ? $this->errorView : $this->exceptionView;
//                    $response->data = $this->renderFile($file, [
//                        'exception' => $exception,
//                    ]);
                }
            } elseif ($response->format === Response::FORMAT_RAW) {
                $response->data = static::convertExceptionToString($exception);
            } else {
                $response->data = $this->convertExceptionToArray($exception);
            }
        }

        $response->send();
    }
}