<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/6/5
 * Time: 3:37 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\oauthserver\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\oauthserver\actions\client\PhabricatorOAuthClientDisableController;
use orangins\modules\oauthserver\actions\client\PhabricatorOAuthClientSecretController;
use orangins\modules\oauthserver\actions\client\PhabricatorOAuthClientViewController;

/**
 * Class IndexController
 * @package orangins\modules\oauthserver\controllers
 * @author 陈妙威
 */
class ClientController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'disable' => PhabricatorOAuthClientDisableController::className(),
            'view' => PhabricatorOAuthClientViewController::className(),
            'secret' => PhabricatorOAuthClientSecretController::className(),
        ];
    }
}