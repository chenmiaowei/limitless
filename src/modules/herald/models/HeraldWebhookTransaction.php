<?php

namespace orangins\modules\herald\models;


use orangins\modules\herald\phid\HeraldWebhookPHIDType;
use orangins\modules\herald\query\HeraldWebhookTransactionQuery;
use orangins\modules\herald\xaction\heraldwebhook\HeraldWebhookTransactionType;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * Class HeraldWebhookTransaction
 * @package orangins\modules\herald\models
 * @author 陈妙威
 */
class HeraldWebhookTransaction  extends PhabricatorModularTransaction
{

    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "herald_webhooktransaction";
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return HeraldWebhookPHIDType::TYPECONST;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return HeraldWebhookTransactionType::class;
    }

    /**
     * @return HeraldWebhookTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new HeraldWebhookTransactionQuery(get_called_class());
    }
}