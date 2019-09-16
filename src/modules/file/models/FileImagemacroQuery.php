<?php

namespace orangins\modules\file\models;

/**
 * This is the ActiveQuery class for [[FileImagemacro]].
 *
 * @see FileImagemacro
 */
class FileImagemacroQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return FileImagemacro[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return FileImagemacro|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
