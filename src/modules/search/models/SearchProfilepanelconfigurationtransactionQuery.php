<?php

namespace orangins\modules\search\models;

/**
 * This is the ActiveQuery class for [[SearchProfilepanelconfigurationtransaction]].
 *
 * @see PhabricatorProfileMenuItemConfigurationTransaction
 */
class SearchProfilepanelconfigurationtransactionQuery extends \orangins\lib\infrastructure\query\PhabricatorQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return PhabricatorProfileMenuItemConfigurationTransaction[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorProfileMenuItemConfigurationTransaction|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
