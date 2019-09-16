<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/25
 * Time: 2:14 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\meta\actions;

use orangins\lib\error\ErrorHandler;
use PhutilSafeHTML;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;
use Yii;

/**
 * Class ErrorAction
 * @package orangins\lib\actions
 * @author 陈妙威
 */
class ErrorAction extends \yii\web\ErrorAction
{
    /**
     * @var bool
     */
    public $enableCsrfValidation  = false;
    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $exception = $this->findException();
        $content = ErrorHandler::convertExceptionToString($exception);
        $dialog = (new AphrontDialogView())
            ->setViewer($this->controller->getViewer())
            ->setTitle($this->findException()->getMessage())
            ->appendChild(new PhutilSafeHTML($content))
            ->addCancelButton(Yii::$app->getHomeUrl());

        $dialogResponse = (new AphrontDialogResponse())->setDialog($dialog);
        return $dialogResponse;
    }
}