<?php

namespace orangins\modules\conduit\models;

use orangins\lib\db\ActiveRecord;
use orangins\modules\conduit\query\PhabricatorConduitLogQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;

/**
 * This is the model class for table "conduit_methodcalllog".
 *
 * @property int $id
 * @property int $connection_id
 * @property string $method
 * @property string $error
 * @property int $duration
 * @property string $caller_phid
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorConduitMethodCallLog extends ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conduit_methodcalllog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['connection_id', 'duration', 'created_at', 'updated_at'], 'integer'],
            [['method', 'error', 'duration'], 'required'],
            [['method', 'caller_phid'], 'string', 'max' => 64],
            [['error'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'connection_id' => Yii::t('app', 'Connection ID'),
            'method' => Yii::t('app', 'Method'),
            'error' => Yii::t('app', 'Error'),
            'duration' => Yii::t('app', 'Duration'),
            'caller_phid' => Yii::t('app', 'Caller PHID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorConduitLogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorConduitLogQuery(get_called_class());
    }

    /**
     * @return int
     */
    public function getConnectionID()
    {
        return $this->connection_id;
    }

    /**
     * @param int $connection_id
     * @return self
     */
    public function setConnectionID($connection_id)
    {
        $this->connection_id = $connection_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return self
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $error
     * @return self
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     * @return self
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return string
     */
    public function getCallerPHID()
    {
        return $this->caller_phid;
    }

    /**
     * @param string $caller_phid
     * @return self
     */
    public function setCallerPHID($caller_phid)
    {
        $this->caller_phid = $caller_phid;
        return $this;
    }
    

    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_USER;
    }

    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }

}
