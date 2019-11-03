<?php

namespace orangins\modules\people\models;

use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldStringIndexStorage;
use Yii;

/**
 * This is the model class for table "user_customfieldstringindex".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $index_key
 * @property string $index_value
 */
class PhabricatorUserCustomFieldStringIndex extends PhabricatorCustomFieldStringIndexStorage
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_customfieldstringindex';
    }
}
