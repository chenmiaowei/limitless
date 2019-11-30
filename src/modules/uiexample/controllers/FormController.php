<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/20
 * Time: 5:45 PM
 */

namespace orangins\modules\uiexample\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\uiexample\actions\form\FormTextCaptchaAction;

/**
 * Class FormsController
 * @package orangins\modules\uiexample\controllers
 */
class FormController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'captcha' => FormTextCaptchaAction::className()
        ];
    }
}