<?php

namespace orangins\modules\spaces\models;

use orangins\modules\spaces\query\PhabricatorSpacesNamespaceQuery;
use Yii;

/**
 * This is the model class for table "spaces_namespace".
 *
 * @property int $id
 * @property string $phid
 * @property string $namespace_name
 * @property string $view_policy
 * @property string $edit_policy
 * @property int $is_default_namespace
 * @property string $description
 * @property int $is_archived
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorSpacesNamespace extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'spaces_namespace';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['namespace_name', 'view_policy', 'edit_policy', 'description', 'is_archived'], 'required'],
            [['is_default_namespace', 'is_archived'], 'integer'],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
            [['namespace_name'], 'string', 'max' => 255],
            [['phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'namespace_name' => Yii::t('app', 'Namespace Name'),
            'view_policy' => Yii::t('app', 'View Policy'),
            'edit_policy' => Yii::t('app', 'Edit Policy'),
            'is_default_namespace' => Yii::t('app', 'Is Default Namespace'),
            'description' => Yii::t('app', 'Description'),
            'is_archived' => Yii::t('app', 'Is Archived'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getNamespaceName()
    {
        return $this->namespace_name;
    }

    /**
     * @param string $namespace_name
     * @return self
     */
    public function setNamespaceName($namespace_name)
    {
        $this->namespace_name = $namespace_name;
        return $this;
    }

    /**
     * @return int
     */
    public function getisDefaultNamespace()
    {
        return $this->is_default_namespace;
    }

    /**
     * @param int $is_default_namespace
     * @return self
     */
    public function setIsDefaultNamespace($is_default_namespace)
    {
        $this->is_default_namespace = $is_default_namespace;
        return $this;
    }

    /**
     * @return int
     */
    public function getisArchived()
    {
        return $this->is_archived;
    }

    /**
     * @param int $is_archived
     * @return self
     */
    public function setIsArchived($is_archived)
    {
        $this->is_archived = $is_archived;
        return $this;
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorSpacesNamespaceQuery
     */
    public static function find()
    {
        return new PhabricatorSpacesNamespaceQuery(get_called_class());
    }
}
