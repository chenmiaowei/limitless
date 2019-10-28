<?php

namespace orangins\modules\herald\models;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;

/**
 * This is the model class for table "herald_action".
 *
 * @property int $id
 * @property int $rule_id
 * @property string $action
 * @property string $target
 */
class HeraldActionRecord extends \orangins\lib\db\ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_action';
    }
    /**
     * @return array
     * @author 陈妙威
     */
    public function behaviors()
    {
        return [];
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['rule_id', 'action', 'target'], 'required'],
            [['rule_id'], 'integer'],
            [['target'], 'string'],
            [['action'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'rule_id' => Yii::t('app', 'Rule ID'),
            'action' => Yii::t('app', 'Action'),
            'target' => Yii::t('app', 'Target'),
        ];
    }

    /**
     * @return int
     */
    public function getRuleID()
    {
        return $this->rule_id;
    }

    /**
     * @param int $rule_id
     * @return self
     */
    public function setRuleID($rule_id)
    {
        $this->rule_id = $rule_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return array
     */
    public function getTarget()
    {
        return $this->target === null ? [] : phutil_json_decode($this->target);
    }

    /**
     * @param string $target
     * @return self
     * @throws \Exception
     */
    public function setTarget($target = [])
    {
        $this->target = $target === null ? null : phutil_json_encode($target);
        return $this;
    }


    /**
     * {@inheritdoc}
     * @return \orangins\modules\herald\query\HeraldActionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \orangins\modules\herald\query\HeraldActionQuery(get_called_class());
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
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return true;
    }

}
