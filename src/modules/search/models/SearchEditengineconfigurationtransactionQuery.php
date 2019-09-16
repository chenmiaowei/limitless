<?php

namespace orangins\modules\search\models;

/**
 * This is the ActiveQuery class for [[SearchEditengineconfigurationtransaction]].
 *
 * @see SearchEditengineconfigurationtransaction
 */
class SearchEditengineconfigurationtransactionQuery extends \orangins\lib\infrastructure\query\PhabricatorQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return SearchEditengineconfigurationtransaction[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return SearchEditengineconfigurationtransaction|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
