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
use orangins\modules\oauthserver\actions\client\PhabricatorOAuthClientEditController;
use orangins\modules\oauthserver\actions\client\PhabricatorOAuthClientListController;
use orangins\modules\oauthserver\actions\PhabricatorOAuthServerAuthController;
use orangins\modules\oauthserver\actions\PhabricatorOAuthServerTokenController;

/**
 * Class IndexController
 * @package orangins\modules\oauthserver\controllers
 * @author 陈妙威
 */
class IndexController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'query' => PhabricatorOAuthClientListController::className(),
            'auth' => PhabricatorOAuthServerAuthController::className(),
            'token' => PhabricatorOAuthServerTokenController::className(),
            'edit' => PhabricatorOAuthClientEditController::className(),
        ];
    }
}