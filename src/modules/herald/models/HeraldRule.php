<?php

namespace orangins\modules\herald\models;

use Exception;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\lib\PhabricatorApplication;
use orangins\modules\herald\capability\HeraldManageGlobalRulesCapability;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\phid\HeraldRulePHIDType;
use orangins\modules\herald\query\HeraldRuleQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilInvalidStateException;
use ReflectionException;
use Throwable;
use Yii;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\interfaces\PhabricatorEditableInterface;
use orangins\modules\herald\editors\HeraldRuleEditor;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\StaleObjectException;
use yii\helpers\Url;

/**
 * This is the model class for table "herald_rule".
 *
 * @property int $id
 * @property string $phid
 * @property string $name
 * @property string $author_phid
 * @property string $content_type
 * @property int $must_match_all
 * @property int $config_version
 * @property string $repetition_policy
 * @property string $rule_type
 * @property int $is_disabled
 * @property string $trigger_object_phid
 * @property int $created_at
 * @property int $updated_at
 */
class HeraldRule extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
    , PhabricatorApplicationTransactionInterface
    , PhabricatorEditableInterface
    , PhabricatorEdgeInterface
{
    use ActiveRecordAuthorTrait;

    const CONFIG_VERSION = 38;
    /**
     * @var string
     */
    private $ruleApplied = self::ATTACHABLE;
    /**
     * @var string
     */
    private $validAuthor = self::ATTACHABLE;
    /**
     * @var string
     */
    private $author = self::ATTACHABLE;
    /**
     * @var HeraldCondition[]
     */
    private $conditions;
    /**
     * @var HeraldActionRecord[]
     */
    private $actions;
    /**
     * @var string
     */
    private $triggerObject = self::ATTACHABLE;
    /**
     *
     */
    const REPEAT_EVERY = 'every';
    /**
     *
     */
    const REPEAT_FIRST = 'first';
    /**
     *
     */
    const REPEAT_CHANGE = 'change';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_rule';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'author_phid', 'content_type', 'must_match_all', 'repetition_policy', 'rule_type'], 'required'],
            [['must_match_all', 'config_version', 'is_disabled', 'created_at', 'updated_at'], 'integer'],
            [['phid', 'author_phid', 'trigger_object_phid'], 'string', 'max' => 64],
            [['name', 'content_type'], 'string', 'max' => 255],
            [['repetition_policy', 'rule_type'], 'string', 'max' => 32],
            [['phid'], 'unique'],
            [['config_version'], 'default', 'value' => self::CONFIG_VERSION],
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
            'name' => Yii::t('app', 'Name'),
            'author_phid' => Yii::t('app', 'Author Phid'),
            'content_type' => Yii::t('app', 'Content Type'),
            'must_match_all' => Yii::t('app', 'Must Match All'),
            'config_version' => Yii::t('app', 'Config Version'),
            'repetition_policy' => Yii::t('app', 'Repetition Policy'),
            'rule_type' => Yii::t('app', 'Rule Type'),
            'is_disabled' => Yii::t('app', 'Is Disabled'),
            'trigger_object_phid' => Yii::t('app', 'Trigger Object Phid'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * @param string $content_type
     * @return self
     */
    public function setContentType($content_type)
    {
        $this->content_type = $content_type;
        return $this;
    }

    /**
     * @return int
     */
    public function getMustMatchAll()
    {
        return $this->must_match_all;
    }

    /**
     * @param int $must_match_all
     * @return self
     */
    public function setMustMatchAll($must_match_all)
    {
        $this->must_match_all = $must_match_all;
        return $this;
    }

    /**
     * @return int
     */
    public function getConfigVersion()
    {
        return $this->config_version !== null ? $this->config_version : self::CONFIG_VERSION;
    }

    /**
     * @param int $config_version
     * @return self
     */
    public function setConfigVersion($config_version)
    {
        $this->config_version = $config_version;
        return $this;
    }

    /**
     * @return string
     */
    public function getRepetitionPolicy()
    {
        return $this->repetition_policy;
    }

    /**
     * @param string $repetition_policy
     * @return self
     */
    public function setRepetitionPolicy($repetition_policy)
    {
        $this->repetition_policy = $repetition_policy;
        return $this;
    }

    /**
     * @return string
     */
    public function getRuleType()
    {
        return $this->rule_type;
    }

    /**
     * @param string $rule_type
     * @return self
     */
    public function setRuleType($rule_type)
    {
        $this->rule_type = $rule_type;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * @param int $is_disabled
     * @return self
     */
    public function setIsDisabled($is_disabled)
    {
        $this->is_disabled = $is_disabled;
        return $this;
    }

    /**
     * @return string
     */
    public function getTriggerObjectPHID()
    {
        return $this->trigger_object_phid;
    }

    /**
     * @param string $trigger_object_phid
     * @return self
     */
    public function setTriggerObjectPHID($trigger_object_phid)
    {
        $this->trigger_object_phid = $trigger_object_phid;
        return $this;
    }


    /**
     * {@inheritdoc}
     * @return HeraldRuleQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new HeraldRuleQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return HeraldRulePHIDType::class;
    }


    /**
     * @param $phid
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getRuleApplied($phid)
    {
        return $this->assertAttachedKey($this->ruleApplied, $phid);
    }

    /**
     * @param $phid
     * @param $applied
     * @return $this
     * @author 陈妙威
     */
    public function setRuleApplied($phid, $applied)
    {
        if ($this->ruleApplied === self::ATTACHABLE) {
            $this->ruleApplied = array();
        }
        $this->ruleApplied[$phid] = $applied;
        return $this;
    }

    /**
     * @return HeraldCondition[]
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function loadConditions()
    {
        if (!$this->getID()) {
            return array();
        }
        return HeraldCondition::find()->andWhere([
            'rule_id' => $this->getID()
        ])->all();
//        return (new HeraldCondition())->loadAllWhere(
//            'ruleID = %d',
//            $this->getID());
    }

    /**
     * @param array $conditions
     * @return $this
     * @author 陈妙威
     */
    public function attachConditions(array $conditions)
    {
        assert_instances_of($conditions, HeraldCondition::className());
        $this->conditions = $conditions;
        return $this;
    }

    /**
     * @return HeraldCondition[]
     * @author 陈妙威
     */
    public function getConditions()
    {
        // TODO: validate conditions have been attached.
        return $this->conditions;
    }

    /**
     * @return HeraldActionRecord[]
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function loadActions()
    {
        if (!$this->getID()) {
            return array();
        }

        return HeraldActionRecord::find()->andWhere([
            'rule_id' => $this->getID()
        ])->all();
//        return (new HeraldActionRecord())->loadAllWhere(
//            'ruleID = %d',
//            $this->getID());
    }

    /**
     * @param array $actions
     * @return $this
     * @author 陈妙威
     */
    public function attachActions(array $actions)
    {
        // TODO: validate actions have been attached.
        assert_instances_of($actions, HeraldActionRecord::className());
        $this->actions = $actions;
        return $this;
    }

    /**
     * @return HeraldActionRecord[]
     * @author 陈妙威
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $conditions
     * @return void
     * @throws PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function saveConditions(array $conditions)
    {
        assert_instances_of($conditions, HeraldCondition::className());
        return $this->saveChildren(
            HeraldCondition::tableName(),
            $conditions);
    }

    /**
     * @param array $actions
     * @return void
     * @throws PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function saveActions(array $actions)
    {
        assert_instances_of($actions, HeraldActionRecord::className());
        return $this->saveChildren(
            HeraldActionRecord::tableName(),
            $actions);
    }

    /**
     * @param $table_name
     * @param array $children
     * @throws PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws Exception
     * @author 陈妙威
     */
    protected function saveChildren($table_name, array $children)
    {
        assert_instances_of($children, ActiveRecord::className());

        if (!$this->getID()) {
            throw new PhutilInvalidStateException('save');
        }

        foreach ($children as $child) {
            $child->setRuleID($this->getID());
        }

        $this->openTransaction();

        Yii::$app->db->createCommand("DELETE FROM {$table_name} WHERE rule_id=:rule_id", [
            ':rule_id' => $this->getID()
        ])->execute();

        foreach ($children as $child) {
            $child->save();
        }
        $this->saveTransaction();
    }

    /**
     * @return false|int
     * @throws Throwable
     * @throws \yii\db\Exception
     * @throws StaleObjectException
     * @author 陈妙威
     */
    public function delete()
    {
        $this->openTransaction();

        HeraldCondition::deleteAll(['rule_id' => $this->getID()]);
        HeraldActionRecord::deleteAll(['rule_id' => $this->getID()]);

        $result = parent::delete();
        $this->saveTransaction();

        return $result;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function hasValidAuthor()
    {
        return $this->assertAttached($this->validAuthor);
    }

    /**
     * @param $valid
     * @return $this
     * @author 陈妙威
     */
    public function attachValidAuthor($valid)
    {
        $this->validAuthor = $valid;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getAuthor()
    {
        return $this->assertAttached($this->author);
    }

    /**
     * @param PhabricatorUser $user
     * @return $this
     * @author 陈妙威
     */
    public function attachAuthor(PhabricatorUser $user)
    {
        $this->author = $user;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isGlobalRule()
    {
        return ($this->getRuleType() === HeraldRuleTypeConfig::RULE_TYPE_GLOBAL);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isPersonalRule()
    {
        return ($this->getRuleType() === HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isObjectRule()
    {
        return ($this->getRuleType() == HeraldRuleTypeConfig::RULE_TYPE_OBJECT);
    }

    /**
     * @param $trigger_object
     * @return $this
     * @author 陈妙威
     */
    public function attachTriggerObject($trigger_object)
    {
        $this->triggerObject = $trigger_object;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getTriggerObject()
    {
        return $this->assertAttached($this->triggerObject);
    }

    /**
     * Get a sortable key for rule execution order.
     *
     * Rules execute in a well-defined order: personal rules first, then object
     * rules, then global rules. Within each rule type, rules execute from lowest
     * ID to highest ID.
     *
     * This ordering allows more powerful rules (like global rules) to override
     * weaker rules (like personal rules) when multiple rules exist which try to
     * affect the same field. Executing from low IDs to high IDs makes
     * interactions easier to understand when adding new rules, because the newest
     * rules always happen last.
     *
     * @return string A sortable key for this rule.
     * @throws Exception
     */
    public function getRuleExecutionOrderSortKey()
    {

        $rule_type = $this->getRuleType();

        switch ($rule_type) {
            case HeraldRuleTypeConfig::RULE_TYPE_PERSONAL:
                $type_order = 1;
                break;
            case HeraldRuleTypeConfig::RULE_TYPE_OBJECT:
                $type_order = 2;
                break;
            case HeraldRuleTypeConfig::RULE_TYPE_GLOBAL:
                $type_order = 3;
                break;
            default:
                throw new Exception(pht('Unknown rule type "%s"!', $rule_type));
        }

        return sprintf('~%d%010d', $type_order, $this->getID());
    }


    /* -(  Repetition Policies  )------------------------------------------------ */


    /**
     * @return string
     * @author 陈妙威
     */
    public function getRepetitionPolicyStringConstant()
    {
        return $this->getRepetitionPolicy();
    }

    /**
     * @param $value
     * @return HeraldRule
     * @throws Exception
     * @author 陈妙威
     */
    public function setRepetitionPolicyStringConstant($value)
    {
        $map = self::getRepetitionPolicyMap();

        if (!isset($map[$value])) {
            throw new Exception(
                pht(
                    'Rule repetition string constant "%s" is unknown.',
                    $value));
        }

        return $this->setRepetitionPolicy($value);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isRepeatEvery()
    {
        return ($this->getRepetitionPolicyStringConstant() === self::REPEAT_EVERY);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isRepeatFirst()
    {
        return ($this->getRepetitionPolicyStringConstant() === self::REPEAT_FIRST);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isRepeatOnChange()
    {
        return ($this->getRepetitionPolicyStringConstant() === self::REPEAT_CHANGE);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function getRepetitionPolicySelectOptionMap()
    {
        $map = self::getRepetitionPolicyMap();
        return ipull($map, 'select');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private static function getRepetitionPolicyMap()
    {
        return array(
            self::REPEAT_EVERY => array(
                'select' => pht('every time this rule matches:'),
            ),
            self::REPEAT_FIRST => array(
                'select' => pht('only the first time this rule matches:'),
            ),
            self::REPEAT_CHANGE => array(
                'select' => pht('if this rule did not match the last time:'),
            ),
        );
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return HeraldRuleEditor|PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new HeraldRuleEditor();
    }

    /**
     * @return HeraldRuleTransaction|PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new HeraldRuleTransaction();
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
     * @return mixed
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws UnknownPropertyException
     * @throws Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        if ($capability == PhabricatorPolicyCapability::CAN_VIEW) {
            return PhabricatorPolicies::getMostOpenPolicy();
        }

        if ($this->isGlobalRule()) {
            $app = 'PhabricatorHeraldApplication';
            $herald = PhabricatorApplication::getByClass($app);
            $global = HeraldManageGlobalRulesCapability::CAPABILITY;
            return $herald->getPolicy($global);
        } else if ($this->isObjectRule()) {
            return $this->getTriggerObject()->getPolicy($capability);
        } else {
            return $this->getAuthorPHID();
        }
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

    /**
     * @param $capability
     * @return string|null
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        if ($capability == PhabricatorPolicyCapability::CAN_VIEW) {
            return null;
        }

        if ($this->isGlobalRule()) {
            return pht(
                'Global Herald rules can be edited by users with the "Can Manage ' .
                'Global Rules" Herald application permission.');
        } else if ($this->isObjectRule()) {
            return pht('Object rules inherit the edit policies of their objects.');
        } else {
            return pht('A personal rule can only be edited by its owner.');
        }
    }


    /* -(  PhabricatorSubscribableInterface  )----------------------------------- */


    /**
     * @param $phid
     * @return bool
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public function isAutomaticallySubscribed($phid)
    {
        return $this->isPersonalRule() && $phid == $this->getAuthorPHID();
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws Throwable
     * @throws StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {

        $this->openTransaction();
        $this->delete();
        $this->saveTransaction();
    }

    /**
     * @return $this
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMonogram()
    {
        return $this->getID();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInfoURI()
    {
        return Url::to(['/herald/index/view', 'id' => $this->getID()]);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return Url::to(['/herald/index/view', 'id' => $this->getID()]);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return 'herald';
    }
}
