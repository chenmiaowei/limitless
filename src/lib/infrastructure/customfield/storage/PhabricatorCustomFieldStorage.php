<?php

namespace orangins\lib\infrastructure\customfield\storage;

use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use Yii;

/**
 * This is the model class for table "user_configuredcustomfieldstorage".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $field_index
 * @property string $field_value
 */
abstract class PhabricatorCustomFieldStorage
    extends ActiveRecord
{

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getApplicationName();

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'field_index', 'field_value'], 'required'],
            [['field_value'], 'string'],
            [['object_phid'], 'string', 'max' => 64],
            [['field_index'], 'string', 'max' => 12],
            [['object_phid', 'field_index'], 'unique', 'targetAttribute' => ['object_phid', 'field_index']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_phid' => Yii::t('app', 'Object PHID'),
            'field_index' => Yii::t('app', 'Field Index'),
            'field_value' => Yii::t('app', 'Field Value'),
        ];
    }

    /**
     * @return string
     */
    public function getObjectPHID()
    {
        return $this->object_phid;
    }

    /**
     * @param string $object_phid
     * @return self
     */
    public function setObjectPHID($object_phid)
    {
        $this->object_phid = $object_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldIndex()
    {
        return $this->field_index;
    }

    /**
     * @param string $field_index
     * @return self
     */
    public function setFieldIndex($field_index)
    {
        $this->field_index = $field_index;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldValue()
    {
        return $this->field_value;
    }

    /**
     * @param string $field_value
     * @return self
     */
    public function setFieldValue($field_value)
    {
        $this->field_value = $field_value;
        return $this;
    }

    /**
     * Get a key which uniquely identifies this storage source.
     *
     * When loading custom fields, fields using sources with the same source key
     * are loaded in bulk.
     *
     * @return string Source identifier.
     */
    final public function getStorageSourceKey()
    {
        return $this->getApplicationName() . '/' . static::tableName();
    }


    /**
     * Load stored data for custom fields.
     *
     * Given a map of fields, return a map with any stored data for those fields.
     * The keys in the result should correspond to the keys in the input. The
     * fields in the list may belong to different objects.
     *
     * @param array<string, PhabricatorCustomField> $fields Map of fields.
     * @return array<String, PhabricatorCustomField> Map of available field data.
     * @throws \yii\base\InvalidConfigException
     */
    final public function loadStorageSourceData(array $fields)
    {
        $map = array();
        $indexes = array();
        $object_phids = array();

        foreach ($fields as $key => $field) {
            $index = $field->getFieldIndex();
            $object_phid = $field->getObject()->getPHID();

            $map[$index][$object_phid] = $key;
            $indexes[$index] = $index;
            $object_phids[$object_phid] = $object_phid;
        }

        if (!$indexes) {
            return array();
        }

//        $conn = $this->establishConnection('r');
//        $rows = queryfx_all(
//            $conn,
//            'SELECT objectPHID, fieldIndex, fieldValue FROM %T
//        WHERE objectPHID IN (%Ls) AND fieldIndex IN (%Ls)',
//            $this->getTableName(),
//            $object_phids,
//            $indexes);
        /** @var static[] $rows */
        $rows = static::find()
            ->andWhere([
                'IN', 'object_phid', $object_phids
            ])
            ->andWhere([
                'IN', 'field_index', $indexes
            ])
            ->all();


        $result = array();
        foreach ($rows as $row) {
            $index = $row->field_index;
            $object_phid = $row->object_phid;
            $value = $row->field_value;

            $key = $map[$index][$object_phid];
            $result[$key] = $value;
        }

        return $result;
    }
}
