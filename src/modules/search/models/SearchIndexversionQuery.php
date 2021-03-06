<?php

namespace orangins\modules\search\models;

/**
 * This is the ActiveQuery class for [[SearchIndexversion]].
 *
 * @see PhabricatorSearchIndexVersion
 */
class SearchIndexversionQuery extends \orangins\lib\infrastructure\query\PhabricatorQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return PhabricatorSearchIndexVersion[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorSearchIndexVersion|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
