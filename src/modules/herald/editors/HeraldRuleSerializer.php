<?php

namespace orangins\modules\herald\editors;

use orangins\modules\herald\models\HeraldActionRecord;
use orangins\modules\herald\models\HeraldCondition;
use orangins\modules\herald\models\HeraldRule;
use Phobject;

/**
 * Serialize for RuleTransactions / Editor.
 */
final class HeraldRuleSerializer extends Phobject
{
    /**
     * @param HeraldRule $rule
     * @return array
     * @author 陈妙威
     */
    public function serializeRule(HeraldRule $rule)
    {
        return $this->serializeRuleComponents(
            (bool)$rule->getMustMatchAll(),
            $rule->getConditions(),
            $rule->getActions(),
            $rule->getRepetitionPolicyStringConstant());
    }

    /**
     * @param $match_all
     * @param array $conditions
     * @param array $actions
     * @param $repetition_policy
     * @return array
     * @author 陈妙威
     */
    public function serializeRuleComponents(
        $match_all,
        array $conditions,
        array $actions,
        $repetition_policy)
    {

        assert_instances_of($conditions, HeraldCondition::class);
        assert_instances_of($actions, HeraldActionRecord::class);

        $conditions_array = array();
        foreach ($conditions as $condition) {
            $conditions_array[] = array(
                'field' => $condition->getFieldName(),
                'condition' => $condition->getFieldCondition(),
                'value' => $condition->getValue(),
            );
        }

        $actions_array = array();
        foreach ($actions as $action) {
            $actions_array[] = array(
                'action' => $action->getAction(),
                'target' => $action->getTarget(),
            );
        }

        return array(
            'match_all' => $match_all,
            'conditions' => $conditions_array,
            'actions' => $actions_array,
            'repetition_policy' => $repetition_policy,
        );
    }

    /**
     * @param array $serialized
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function deserializeRuleComponents(array $serialized)
    {
        $deser_conditions = array();
        foreach ($serialized['conditions'] as $condition) {
            $deser_conditions[] = (new HeraldCondition())
                ->setFieldName($condition['field'])
                ->setFieldCondition($condition['condition'])
                ->setValue($condition['value']);
        }

        $deser_actions = array();
        foreach ($serialized['actions'] as $action) {
            $deser_actions[] = (new HeraldActionRecord())
                ->setAction($action['action'])
                ->setTarget($action['target']);
        }

        return array(
            'match_all' => $serialized['match_all'],
            'conditions' => $deser_conditions,
            'actions' => $deser_actions,
            'repetition_policy' => $serialized['repetition_policy'],
        );
    }

}
