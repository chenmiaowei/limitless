<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 5:47 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\models;


use orangins\modules\tag\phid\PhabricatorTagsTagPHIDType;
use orangins\modules\tag\query\PhabricatorTagTransactionQuery;
use orangins\modules\tag\xaction\PhabricatorTagTransactionType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * Class PhabricatorTagTransaction
 * @package orangins\modules\tag\models
 * @author 陈妙威
 */
class PhabricatorTagTransaction extends PhabricatorModularTransaction
{

    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "tag_transactions";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'tag';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorTagsTagPHIDType::TYPECONST;
    }

    /**
     * @author 陈妙威
     */
    public function getApplicationTransactionCommentObject()
    {
        return null;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorTagTransactionType::class;
    }

    /**
     * @return PhabricatorTagTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorTagTransactionQuery(get_called_class());
    }
}