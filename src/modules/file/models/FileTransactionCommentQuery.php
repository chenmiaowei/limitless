<?php

namespace orangins\modules\file\models;

/**
 * This is the ActiveQuery class for [[FileTransactionComment]].
 *
 * @see PhabricatorFileTransactionComment
 */
class FileTransactionCommentQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return PhabricatorFileTransactionComment[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorFileTransactionComment|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
