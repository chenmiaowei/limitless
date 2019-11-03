<?php

namespace orangins\lib\infrastructure\customfield\storage;

use Yii;

/**
 * Class PhabricatorCustomFieldNumericIndexStorage
 * @package orangins\lib\infrastructure\customfield\storage
 * @author 陈妙威
 * @property int $id
 * @property string $object_phid
 * @property string $index_key
 * @property string $index_value
 */
abstract class PhabricatorCustomFieldNumericIndexStorage
    extends PhabricatorCustomFieldIndexStorage
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'index_key', 'index_value'], 'required'],
            [['object_phid'], 'string', 'max' => 64],
            [['index_key'], 'string', 'max' => 12],
            [['index_value'], 'string', 'max' => 20],
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
            'index_key' => Yii::t('app', 'Index Key'),
            'index_value' => Yii::t('app', 'Index Value'),
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
    public function getIndexKey()
    {
        return $this->index_key;
    }

    /**
     * @param string $index_key
     * @return self
     */
    public function setIndexKey($index_key)
    {
        $this->index_key = $index_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getIndexValue()
    {
        return $this->index_value;
    }

    /**
     * @param string $index_value
     * @return self
     */
    public function setIndexValue($index_value)
    {
        $this->index_value = $index_value;
        return $this;
    }


    /**
     * @return mixed|\PhutilQueryString
     * @author 陈妙威
     */
    public function formatForInsert()
    {
        return [
            'object_phid' => $this->getObjectPHID(),
            'index_key' => $this->getIndexKey(),
            'index_value' => $this->getIndexValue(),
        ];
//        return qsprintf(
//            $conn,
//            '(%s, %s, %d)',
//            $this->getObjectPHID(),
//            $this->getIndexKey(),
//            $this->getIndexValue());
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getIndexValueType()
    {
        return 'int';
    }
}
