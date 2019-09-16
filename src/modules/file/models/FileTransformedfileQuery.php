<?php

namespace orangins\modules\file\models;

/**
 * This is the ActiveQuery class for [[FileTransformedfile]].
 *
 * @see PhabricatorTransformedFile
 */
class FileTransformedfileQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return PhabricatorTransformedFile[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorTransformedFile|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
