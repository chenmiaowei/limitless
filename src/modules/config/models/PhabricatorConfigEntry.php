<?php

namespace orangins\modules\config\models;

use Exception;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\request\AphrontRequest;
use orangins\modules\config\editor\PhabricatorConfigEditor;
use orangins\modules\config\phid\PhabricatorConfigConfigPHIDType;
use orangins\modules\config\query\PhabricatorConfigEntryQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;

/**
 * This is the model class for table "config".
 *
 * @property int $id
 * @property string $phid
 * @property string $namespace 命名空间
 * @property string $config_key 配置项
 * @property string $value 值
 * @property int $is_deleted
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorConfigEntry extends ActiveRecordPHID
    implements
    PhabricatorApplicationTransactionInterface,
    PhabricatorPolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['namespace', 'config_key'], 'required'],
            [['value'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'namespace', 'config_key'], 'string', 'max' => 64],
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
            'namespace' => Yii::t('app', '命名空间'),
            'config_key' => Yii::t('app', '配置项'),
            'value' => Yii::t('app', '值'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorConfigConfigPHIDType::class;
    }

    /**
     * @param $key
     * @return self
     * @author 陈妙威
     */
    public static function loadConfigEntry($key)
    {
        $config_entry = PhabricatorConfigEntry::find()
            ->andWhere([
                'config_key' => $key,
                'namespace' => 'default'
            ])->one();
        if (!$config_entry) {
            $config_entry = (new PhabricatorConfigEntry())
                ->setConfigKey($key)
                ->setNamespace('default')
                ->setIsDeleted(0);
        }

        return $config_entry;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     * @return self
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
    }

    /**
     * @param string $config_key
     * @return self
     */
    public function setConfigKey($config_key)
    {
        $this->config_key = $config_key;
        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getValue()
    {
        return $this->value === null ? null : phutil_json_decode($this->value);
    }

    /**
     * @param string $value
     * @return self
     * @throws Exception
     */
    public function setValue($value)
    {
        $this->value = $value === null ? null : phutil_json_encode($value);
        return $this;
    }

    /**
     * @return int
     */
    public function getisDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param int $is_deleted
     * @return self
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
    }


    /**
     * @return PhabricatorConfigEntryQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorConfigEntryQuery(get_called_class());
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorConfigEditor|PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorConfigEditor();
    }

    /**
     * @return $this|ActiveRecord
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorConfigTransaction|PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorConfigTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request)
    {

        return $timeline;
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_ADMIN;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }

}
