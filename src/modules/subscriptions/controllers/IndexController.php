<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/25
 * Time: 1:02 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\subscriptions\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\subscriptions\actions\SubscriptionEditAction;
use orangins\modules\subscriptions\actions\SubscriptionDeleteAction;
use orangins\modules\subscriptions\actions\SubscriptionMuteAction;

/**
 * Class IndexController
 * @package orangins\modules\subscriptions\controllers
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
            'add' => SubscriptionEditAction::class,
            'delete' => SubscriptionEditAction::class,
            'mute' => SubscriptionMuteAction::class,
        ];
    }
}