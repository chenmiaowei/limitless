<?php

namespace orangins\modules\people\models;

use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldNumericIndexStorage;
use Yii;

/**
 * This is the model class for table "user_customfieldnumericindex".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $index_key
 * @property string $index_value
 */
class PhabricatorUserCustomFieldNumericIndex extends PhabricatorCustomFieldNumericIndexStorage
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_customfieldnumericindex';
    }
}
