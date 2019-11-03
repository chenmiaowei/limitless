<?php

namespace orangins\modules\people\models;

use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldStorage;
use Yii;

/**
 * This is the model class for table "user_configuredcustomfieldstorage".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $field_index
 * @property string $field_value
 */
class PhabricatorUserConfiguredCustomFieldStorage extends PhabricatorCustomFieldStorage
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_configuredcustomfieldstorage';
    }


}
