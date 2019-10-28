<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 2:53 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\transactions\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionCommentEditController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionCommentHistoryController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionCommentQuoteController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionCommentRawController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionCommentRemoveController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionDetailController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionRemarkupPreviewController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionShowOlderController;
use orangins\modules\transactions\actions\PhabricatorApplicationTransactionValueController;

/**
 * Class IndexController
 * @package orangins\modules\transactions\controllers
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
            'edit' => PhabricatorApplicationTransactionCommentEditController::className(),
            'remove' => PhabricatorApplicationTransactionCommentRemoveController::className(),
            'history' => PhabricatorApplicationTransactionCommentHistoryController::className(),
            'quote' => PhabricatorApplicationTransactionCommentQuoteController::className(),
            'raw' => PhabricatorApplicationTransactionCommentRawController::className(),
            'detail' => PhabricatorApplicationTransactionDetailController::className(),
            'showolder' => PhabricatorApplicationTransactionShowOlderController::className(),
            'old' => PhabricatorApplicationTransactionValueController::className(),
            'value' => PhabricatorApplicationTransactionValueController::className(),
            'remarkuppreview' => PhabricatorApplicationTransactionRemarkupPreviewController::className(),
        ];
    }
}