<?php

namespace orangins\modules\file\models;

/**
 * This is the ActiveQuery class for [[FileFilenameNgrams]].
 *
 * @see PhabricatorFileNameNgrams
 */
class FileFilenameNgramsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return PhabricatorFileNameNgrams[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorFileNameNgrams|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
