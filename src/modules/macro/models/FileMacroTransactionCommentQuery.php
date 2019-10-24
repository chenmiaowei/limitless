<?php

namespace orangins\modules\macro\models;

/**
 * This is the ActiveQuery class for [[FileMacroTransactionComment]].
 *
 * @see FileMacroTransactionComment
 */
class FileMacroTransactionCommentQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return FileMacroTransactionComment[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return FileMacroTransactionComment|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
