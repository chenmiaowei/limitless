<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/7/16
 * Time: 12:12 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\auth\controllers;


use orangins\lib\controllers\PhabricatorController;
use orangins\modules\auth\actions\mobile\PhabricatorSendSMSAction;

/**
 * Class MobileController
 * @package orangins\modules\auth\controllers
 * @author 陈妙威
 */
class MobileController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'send-sms' => PhabricatorSendSMSAction::className(),
        ];
    }
}