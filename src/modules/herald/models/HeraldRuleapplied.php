<?php

namespace orangins\modules\herald\models;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;

/**
 * This is the model class for table "herald_ruleapplied".
 *
 * @property int $rule_id
 * @property string $phid
 */
class HeraldRuleapplied extends \orangins\lib\db\ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_ruleapplied';
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
            [['phid'], 'required'],
            [['phid'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'rule_id' => Yii::t('app', 'Rule ID'),
            'phid' => Yii::t('app', 'Phid'),
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
    public function getPHID()
    {
        return $this->phid;
    }

    /**
     * @param string $phid
     * @return self
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }


    /**
     * {@inheritdoc}
     * @return \orangins\modules\herald\query\HeraldRuleappliedQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \orangins\modules\herald\query\HeraldRuleappliedQuery(get_called_class());
    }

    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */
    /**
    * @return array|string[]
    * @author 陈妙威
    */
    public function getCapabilities() {
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
    public function getPolicy($capability) {
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
    * @param $capability
    * @param PhabricatorUser $viewer
    * @return bool
    * @author 陈妙威
    */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
        return true;
    }

}
