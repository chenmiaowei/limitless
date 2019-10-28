<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019-10-25
 * Time: 13:26
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\herald\controllers;


use orangins\lib\controllers\PhabricatorController;
use orangins\modules\herald\actions\HeraldTranscriptController;
use orangins\modules\herald\actions\HeraldTranscriptListController;
use orangins\modules\herald\actions\HeraldWebhookEditController;
use orangins\modules\herald\actions\HeraldWebhookKeyController;
use orangins\modules\herald\actions\HeraldWebhookListController;
use orangins\modules\herald\actions\HeraldWebhookTestController;
use orangins\modules\herald\actions\HeraldWebhookViewController;

class WebhookController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'query' => HeraldWebhookListController::class,
            'view' => HeraldWebhookViewController::class,
            'edit' => HeraldWebhookEditController::class,
            'test' => HeraldWebhookTestController::class,
            'key' => HeraldWebhookKeyController::class,
        ];
    }
}