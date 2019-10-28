<?php

namespace orangins\modules\herald\models;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;

/**
 * This is the model class for table "herald_condition".
 *
 * @property int $id
 * @property int $rule_id
 * @property string $field_name
 * @property string $field_condition
 * @property string $value
 */
class HeraldCondition extends \orangins\lib\db\ActiveRecord
    implements PhabricatorPolicyInterface
{



    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_condition';
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
            [['rule_id', 'field_name', 'field_condition', 'value'], 'required'],
            [['rule_id'], 'integer'],
            [['value'], 'string'],
            [['field_name', 'field_condition'], 'string', 'max' => 255],
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
            'field_name' => Yii::t('app', 'Field Name'),
            'field_condition' => Yii::t('app', 'Field Condition'),
            'value' => Yii::t('app', 'Value'),
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
    public function getFieldName()
    {
        return $this->field_name;
    }

    /**
     * @param string $field_name
     * @return self
     */
    public function setFieldName($field_name)
    {
        $this->field_name = $field_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldCondition()
    {
        return $this->field_condition;
    }

    /**
     * @param string $field_condition
     * @return self
     */
    public function setFieldCondition($field_condition)
    {
        $this->field_condition = $field_condition;
        return $this;
    }

    /**
     * @return array
     */
    public function getValue()
    {
        return $this->value === null ? [] : phutil_json_decode($this->value);
    }

    /**
     * @param string $target
     * @return self
     * @throws \Exception
     */
    public function setValue($target)
    {
        $this->value = $target === null ? null : phutil_json_encode($target);
        return $this;
    }

    

    /**
     * {@inheritdoc}
     * @return \orangins\modules\herald\query\HeraldConditionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \orangins\modules\herald\query\HeraldConditionQuery(get_called_class());
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
