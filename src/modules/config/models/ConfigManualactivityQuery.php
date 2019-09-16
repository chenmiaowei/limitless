<?php

namespace orangins\modules\config\models;

/**
 * This is the ActiveQuery class for [[ConfigManualactivity]].
 *
 * @see PhabricatorConfigManualActivity
 */
class ConfigManualactivityQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return PhabricatorConfigManualActivity[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorConfigManualActivity|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
