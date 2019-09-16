<?php

namespace orangins\modules\userservice\models;

/**
 * This is the ActiveQuery class for [[UserserviceCache]].
 *
 * @see UserserviceCache
 */
class UserserviceCacheQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return UserserviceCache[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return UserserviceCache|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
