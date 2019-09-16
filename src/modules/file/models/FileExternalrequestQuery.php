<?php

namespace orangins\modules\file\models;

/**
 * This is the ActiveQuery class for [[FileExternalrequest]].
 *
 * @see FileExternalrequest
 */
class FileExternalrequestQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return FileExternalrequest[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return FileExternalrequest|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
