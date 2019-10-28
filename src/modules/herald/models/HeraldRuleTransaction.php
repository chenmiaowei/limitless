<?php

namespace orangins\modules\herald\models;


use orangins\modules\herald\phid\HeraldRulePHIDType;
use orangins\modules\herald\query\HeraldRuleTransactionQuery;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleTransactionType;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * Class HeraldRuleTransaction
 * @package orangins\modules\herald\models
 * @author 陈妙威
 */
class HeraldRuleTransaction  extends PhabricatorModularTransaction
{

    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "herald_ruletransaction";
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return HeraldRulePHIDType::TYPECONST;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return HeraldRuleTransactionType::class;
    }

    /**
     * @return HeraldRuleTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new HeraldRuleTransactionQuery(get_called_class());
    }
}