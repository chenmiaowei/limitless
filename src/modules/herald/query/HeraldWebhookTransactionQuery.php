<?php



namespace orangins\modules\herald\query;

use orangins\modules\herald\models\HeraldWebhookTransaction;
use orangins\modules\file\models\PhabricatorFileTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorFileTransactionQuery
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class HeraldWebhookTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorFileTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new HeraldWebhookTransaction();
    }
}


