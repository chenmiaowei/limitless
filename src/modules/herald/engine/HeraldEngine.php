<?php

namespace orangins\modules\herald\engine;

use AphrontQueryException;
use Exception;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\engine\exception\HeraldInvalidConditionException;
use orangins\modules\herald\engine\exception\HeraldRecursiveConditionsException;
use orangins\modules\herald\models\HeraldActionRecord;
use orangins\modules\herald\models\HeraldCondition;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\models\HeraldRuleapplied;
use orangins\modules\herald\models\HeraldTranscript;
use orangins\modules\herald\models\transcript\HeraldApplyTranscript;
use orangins\modules\herald\models\transcript\HeraldConditionTranscript;
use orangins\modules\herald\models\transcript\HeraldObjectTranscript;
use orangins\modules\herald\models\transcript\HeraldRuleTranscript;
use orangins\modules\herald\systemaction\HeraldAction;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use Phobject;
use PhutilInvalidStateException;
use ReflectionException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\IntegrityException;

/**
 * Class HeraldEngine
 * @package orangins\modules\herald\engine
 * @author 陈妙威
 */
final class HeraldEngine extends Phobject
{

    /**
     * @var array
     */
    protected $rules = array();
    /**
     * @var array
     */
    protected $results = array();
    /**
     * @var array
     */
    protected $stack = array();
    /**
     * @var
     */
    protected $activeRule;
    /**
     * @var HeraldTranscript
     */
    protected $transcript;

    /**
     * @var array
     */
    protected $fieldCache = array();
    /**
     * @var HeraldAdapter
     */
    protected $object;
    /**
     * @var
     */
    private $dryRun;

    /**
     * @var array
     */
    private $forbiddenFields = array();
    /**
     * @var array
     */
    private $forbiddenActions = array();
    /**
     * @var array
     */
    private $skipEffects = array();

    /**
     * @param $dry_run
     * @return $this
     * @author 陈妙威
     */
    public function setDryRun($dry_run)
    {
        $this->dryRun = $dry_run;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDryRun()
    {
        return $this->dryRun;
    }

    /**
     * @param $phid
     * @return object
     * @author 陈妙威
     */
    public function getRule($phid)
    {
        return idx($this->rules, $phid);
    }

    /**
     * @param HeraldAdapter $adapter
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function loadRulesForAdapter(HeraldAdapter $adapter)
    {
        return HeraldRule::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withDisabled(false)
            ->withContentTypes(array($adapter->getAdapterContentType()))
            ->needConditionsAndActions(true)
            ->needAppliedToPHIDs(array($adapter->getPHID()))
            ->needValidateAuthors(true)
            ->execute();
    }

    /**
     * @param HeraldAdapter $adapter
     * @return mixed
     * @throws InvalidConfigException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public static function loadAndApplyRules(HeraldAdapter $adapter)
    {
        $engine = new HeraldEngine();

        $rules = $engine->loadRulesForAdapter($adapter);
        $effects = $engine->applyRules($rules, $adapter);
        $engine->applyEffects($effects, $adapter, $rules);

        return $engine->getTranscript();
    }

    /**
     * @param array $rules
     * @param HeraldAdapter $object
     * @return array
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws Exception
     * @author 陈妙威
     */
    public function applyRules(array $rules, HeraldAdapter $object)
    {
        assert_instances_of($rules, HeraldRule::className());
        $t_start = microtime(true);

        // Rules execute in a well-defined order: sort them into execution order.
        /** @var HeraldRule[] $rules */
        $rules = msort($rules, 'getRuleExecutionOrderSortKey');
        $rules = mpull($rules, null, 'getPHID');

        $this->transcript = new HeraldTranscript();
        $this->transcript->setObjectPHID((string)$object->getPHID());
        $this->fieldCache = array();
        $this->results = array();
        $this->rules = $rules;
        $this->object = $object;

        $effects = array();
        foreach ($rules as $phid => $rule) {
            $this->stack = array();

            $is_first_only = $rule->isRepeatFirst();

            try {
                if (!$this->getDryRun() &&
                    $is_first_only &&
                    $rule->getRuleApplied($object->getPHID())) {
                    // This is not a dry run, and this rule is only supposed to be
                    // applied a single time, and it's already been applied...
                    // That means automatic failure.
                    $this->newRuleTranscript($rule)
                        ->setResult(false)
                        ->setReason(
                            pht(
                                'This rule is only supposed to be repeated a single time, ' .
                                'and it has already been applied.'));

                    $rule_matches = false;
                } else {
                    if ($this->isForbidden($rule, $object)) {
                        $this->newRuleTranscript($rule)
                            ->setResult(HeraldRuleTranscript::RESULT_FORBIDDEN)
                            ->setReason(
                                pht(
                                    'Object state is not compatible with rule.'));

                        $rule_matches = false;
                    } else {
                        $rule_matches = $this->doesRuleMatch($rule, $object);
                    }
                }
            } catch (HeraldRecursiveConditionsException $ex) {
                $names = array();
                foreach ($this->stack as $rule_phid => $ignored) {
                    $names[] = '"' . $rules[$rule_phid]->getName() . '"';
                }
                $names = implode(', ', $names);
                foreach ($this->stack as $rule_phid => $ignored) {
                    $this->newRuleTranscript($rules[$rule_phid])
                        ->setResult(false)
                        ->setReason(
                            pht(
                                "Rules %s are recursively dependent upon one another! " .
                                "Don't do this! You have formed an unresolvable cycle in the " .
                                "dependency graph!",
                                $names));
                }
                $rule_matches = false;
            }
            $this->results[$phid] = $rule_matches;

            if ($rule_matches) {
                foreach ($this->getRuleEffects($rule, $object) as $effect) {
                    $effects[] = $effect;
                }
            }
        }

        $object_transcript = new HeraldObjectTranscript();
        $object_transcript->setPHID($object->getPHID());
        $object_transcript->setName($object->getHeraldName());
        $object_transcript->setType($object->getAdapterContentType());
        $object_transcript->setFields($this->fieldCache);

        $this->transcript->setObjectTranscript($object_transcript);

        $t_end = microtime(true);

        $this->transcript->setDuration($t_end - $t_start);

        return $effects;
    }

    /**
     * @param array $effects
     * @param HeraldAdapter $adapter
     * @param array $rules
     * @throws Exception
     * @author 陈妙威
     */
    public function applyEffects(
        array $effects,
        HeraldAdapter $adapter,
        array $rules)
    {
        assert_instances_of($effects, HeraldEffect::class);
        assert_instances_of($rules, HeraldRule::class);

        $this->transcript->setDryRun((int)$this->getDryRun());

        if ($this->getDryRun()) {
            $xscripts = array();
            foreach ($effects as $effect) {
                $xscripts[] = new HeraldApplyTranscript(
                    $effect,
                    false,
                    pht('This was a dry run, so no actions were actually taken.'));
            }
        } else {
            $xscripts = $adapter->applyHeraldEffects($effects);
        }

        assert_instances_of($xscripts, HeraldApplyTranscript::class);
        foreach ($xscripts as $apply_xscript) {
            $this->transcript->addApplyTranscript($apply_xscript);
        }

        // For dry runs, don't mark the rule as having applied to the object.
        if ($this->getDryRun()) {
            return;
        }

        // Update the "applied" state table. How this table works depends on the
        // repetition policy for the rule.
        //
        // REPEAT_EVERY: We delete existing rows for the rule, then write nothing.
        // This policy doesn't use any state.
        //
        // REPEAT_FIRST: We keep existing rows, then write additional rows for
        // rules which fired. This policy accumulates state over the life of the
        // object.
        //
        // REPEAT_CHANGE: We delete existing rows, then write all the rows which
        // matched. This policy only uses the state from the previous run.

        /** @var HeraldRule[] $rules */
        $rules = mpull($rules, null, 'getID');
        $rule_ids = mpull($xscripts, 'getRuleID');

        $delete_ids = array();
        foreach ($rules as $rule_id => $rule) {
            if ($rule->isRepeatFirst()) {
                continue;
            }
            $delete_ids[] = $rule_id;
        }

        $applied_ids = array();
        foreach ($rule_ids as $rule_id) {
            if (!$rule_id) {
                // Some apply transcripts are purely informational and not associated
                // with a rule, e.g. carryover emails from earlier revisions.
                continue;
            }

            $rule = idx($rules, $rule_id);
            if (!$rule) {
                continue;
            }

            if ($rule->isRepeatFirst() || $rule->isRepeatOnChange()) {
                $applied_ids[] = $rule_id;
            }
        }

        // Also include "only if this rule did not match the last time" rules
        // which matched but were skipped in the "applied" list.
        foreach ($this->skipEffects as $rule_id => $ignored) {
            $applied_ids[] = $rule_id;
        }

        if ($delete_ids || $applied_ids) {

            if ($delete_ids) {
                HeraldRuleapplied::deleteAll([
                   'phid' =>  $adapter->getPHID(),
                    'rule_id' => $delete_ids
                ]);
            }

            if ($applied_ids) {
                $sql = array();
                foreach ($applied_ids as $id) {
                    $sql[] = [
                        'phid' => $adapter->getPHID(),
                        'rule_id' => $id
                    ];
                }
                (new HeraldRuleapplied())->getDb()->createCommand()->batchInsert(HeraldRuleapplied::tableName(), [
                    'phid',
                    'rule_id',
                ], $sql)->execute();
            }
        }
    }

    /**
     * @return mixed
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @author 陈妙威
     */
    public function getTranscript()
    {
        $this->transcript->save();
        return $this->transcript;
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldAdapter $object
     * @return bool|mixed|null
     * @throws HeraldRecursiveConditionsException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public function doesRuleMatch(
        HeraldRule $rule,
        HeraldAdapter $object)
    {

        $phid = $rule->getPHID();

        if (isset($this->results[$phid])) {
            // If we've already evaluated this rule because another rule depends
            // on it, we don't need to reevaluate it.
            return $this->results[$phid];
        }

        if (isset($this->stack[$phid])) {
            // We've recursed, fail all of the rules on the stack. This happens when
            // there's a dependency cycle with "Rule conditions match for rule ..."
            // conditions.
            foreach ($this->stack as $rule_phid => $ignored) {
                $this->results[$rule_phid] = false;
            }
            throw new HeraldRecursiveConditionsException();
        }

        $this->stack[$phid] = true;

        $all = $rule->getMustMatchAll();

        $conditions = $rule->getConditions();

        $result = null;

        $local_version = (new HeraldRule())->getConfigVersion();
        if ($rule->getConfigVersion() > $local_version) {
            $reason = pht(
                'Rule could not be processed, it was created with a newer version ' .
                'of Herald.');
            $result = false;
        } else if (!$conditions) {
            $reason = pht(
                'Rule failed automatically because it has no conditions.');
            $result = false;
        } else if (!$rule->hasValidAuthor()) {
            $reason = pht(
                'Rule failed automatically because its owner is invalid ' .
                'or disabled.');
            $result = false;
        } else if (!$this->canAuthorViewObject($rule, $object)) {
            $reason = pht(
                'Rule failed automatically because it is a personal rule and its ' .
                'owner can not see the object.');
            $result = false;
        } else if (!$this->canRuleApplyToObject($rule, $object)) {
            $reason = pht(
                'Rule failed automatically because it is an object rule which is ' .
                'not relevant for this object.');
            $result = false;
        } else {
            foreach ($conditions as $condition) {
                try {
                    $this->getConditionObjectValue($condition, $object);
                } catch (Exception $ex) {
                    $reason = pht(
                        'Field "%s" does not exist!',
                        $condition->getFieldName());
                    $result = false;
                    break;
                }

                $match = $this->doesConditionMatch($rule, $condition, $object);

                if (!$all && $match) {
                    $reason = pht('Any condition matched.');
                    $result = true;
                    break;
                }

                if ($all && !$match) {
                    $reason = pht('Not all conditions matched.');
                    $result = false;
                    break;
                }
            }

            if ($result === null) {
                if ($all) {
                    $reason = pht('All conditions matched.');
                    $result = true;
                } else {
                    $reason = pht('No conditions matched.');
                    $result = false;
                }
            }
        }

        // If this rule matched, and is set to run "if it did not match the last
        // time", and we matched the last time, we're going to return a match in
        // the transcript but set a flag so we don't actually apply any effects.

        // We need the rule to match so that storage gets updated properly. If we
        // just pretend the rule didn't match it won't cause any effects (which
        // is correct), but it also won't set the "it matched" flag in storage,
        // so the next run after this one would incorrectly trigger again.

        $is_dry_run = $this->getDryRun();
        if ($result && !$is_dry_run) {
            $is_on_change = $rule->isRepeatOnChange();
            if ($is_on_change) {
                $did_apply = $rule->getRuleApplied($object->getPHID());
                if ($did_apply) {
                    $reason = pht(
                        'This rule matched, but did not take any actions because it ' .
                        'is configured to act only if it did not match the last time.');

                    $this->skipEffects[$rule->getID()] = true;
                }
            }
        }

        $this->newRuleTranscript($rule)
            ->setResult($result)
            ->setReason($reason);

        return $result;
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldCondition $condition
     * @param HeraldAdapter $object
     * @return bool
     * @throws HeraldRecursiveConditionsException
     * @throws UnknownPropertyException
     * @throws Exception
     * @author 陈妙威
     */
    protected function doesConditionMatch(
        HeraldRule $rule,
        HeraldCondition $condition,
        HeraldAdapter $object)
    {

        $object_value = $this->getConditionObjectValue($condition, $object);
        $transcript = $this->newConditionTranscript($rule, $condition);

        try {
            $result = $object->doesConditionMatch(
                $this,
                $rule,
                $condition,
                $object_value);
        } catch (HeraldInvalidConditionException $ex) {
            $result = false;
            $transcript->setNote($ex->getMessage());
        }

        $transcript->setResult($result);

        return $result;
    }

    /**
     * @param HeraldCondition $condition
     * @param HeraldAdapter $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function getConditionObjectValue(
        HeraldCondition $condition,
        HeraldAdapter $object)
    {

        $field = $condition->getFieldName();
        return $this->getObjectFieldValue($field);
    }

    /**
     * @param $field
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getObjectFieldValue($field)
    {
        if (!array_key_exists($field, $this->fieldCache)) {
            $this->fieldCache[$field] = $this->object->getHeraldField($field);
        }

        return $this->fieldCache[$field];
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldAdapter $object
     * @return array
     * @author 陈妙威
     */
    protected function getRuleEffects(
        HeraldRule $rule,
        HeraldAdapter $object)
    {

        $rule_id = $rule->getID();
        if (isset($this->skipEffects[$rule_id])) {
            return array();
        }

        $effects = array();
        foreach ($rule->getActions() as $action) {
            $effect = (new HeraldEffect())
                ->setObjectPHID($object->getPHID())
                ->setAction($action->getAction())
                ->setTarget($action->getTarget())
                ->setRule($rule);

            $name = $rule->getName();
            $id = $rule->getID();
            $effect->setReason(
                pht(
                    'Conditions were met for %s',
                    "H{$id} {$name}"));

            $effects[] = $effect;
        }
        return $effects;
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldAdapter $adapter
     * @return bool
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function canAuthorViewObject(
        HeraldRule $rule,
        HeraldAdapter $adapter)
    {

        // Authorship is irrelevant for global rules and object rules.
        if ($rule->isGlobalRule() || $rule->isObjectRule()) {
            return true;
        }

        // The author must be able to create rules for the adapter's content type.
        // In particular, this means that the application must be installed and
        // accessible to the user. For example, if a user writes a Differential
        // rule and then loses access to Differential, this disables the rule.
        $enabled = HeraldAdapter::getEnabledAdapterMap($rule->getAuthor());
        if (empty($enabled[$adapter->getAdapterContentType()])) {
            return false;
        }

        // Finally, the author must be able to see the object itself. You can't
        // write a personal rule that CC's you on revisions you wouldn't otherwise
        // be able to see, for example.
        $object = $adapter->getObject();
        return PhabricatorPolicyFilter::hasCapability(
            $rule->getAuthor(),
            $object,
            PhabricatorPolicyCapability::CAN_VIEW);
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldAdapter $adapter
     * @return bool
     * @author 陈妙威
     */
    private function canRuleApplyToObject(
        HeraldRule $rule,
        HeraldAdapter $adapter)
    {

        // Rules which are not object rules can apply to anything.
        if (!$rule->isObjectRule()) {
            return true;
        }

        $trigger_phid = $rule->getTriggerObjectPHID();
        $object_phids = $adapter->getTriggerObjectPHIDs();

        if ($object_phids) {
            if (in_array($trigger_phid, $object_phids)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param HeraldRule $rule
     * @return mixed
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    private function newRuleTranscript(HeraldRule $rule)
    {
        $xscript = (new HeraldRuleTranscript())
            ->setRuleID($rule->getID())
            ->setRuleName($rule->getName())
            ->setRuleOwner($rule->getAuthorPHID());

        $this->transcript->addRuleTranscript($xscript);

        return $xscript;
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldCondition $condition
     * @return mixed
     * @author 陈妙威
     */
    private function newConditionTranscript(
        HeraldRule $rule,
        HeraldCondition $condition)
    {

        $xscript = (new HeraldConditionTranscript())
            ->setRuleID($rule->getID())
            ->setConditionID($condition->getID())
            ->setFieldName($condition->getFieldName())
            ->setCondition($condition->getFieldCondition())
            ->setTestValue($condition->getValue());

        $this->transcript->addConditionTranscript($xscript);

        return $xscript;
    }

    /**
     * @param HeraldAdapter $adapter
     * @param HeraldRule $rule
     * @param HeraldActionRecord $action
     * @return HeraldApplyTranscript
     * @author 陈妙威
     */
    private function newApplyTranscript(
        HeraldAdapter $adapter,
        HeraldRule $rule,
        HeraldActionRecord $action)
    {

        $effect = (new HeraldEffect())
            ->setObjectPHID($adapter->getPHID())
            ->setAction($action->getAction())
            ->setTarget($action->getTarget())
            ->setRule($rule);

        $xscript = new HeraldApplyTranscript($effect, false);

        $this->transcript->addApplyTranscript($xscript);

        return $xscript;
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldAdapter $adapter
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    private function isForbidden(
        HeraldRule $rule,
        HeraldAdapter $adapter)
    {

        $forbidden = $adapter->getForbiddenActions();
        if (!$forbidden) {
            return false;
        }

        $forbidden = array_fuse($forbidden);

        $is_forbidden = false;

        foreach ($rule->getConditions() as $condition) {
            $field_key = $condition->getFieldName();

            if (!isset($this->forbiddenFields[$field_key])) {
                $reason = null;

                try {
                    $states = $adapter->getRequiredFieldStates($field_key);
                } catch (Exception $ex) {
                    $states = array();
                }

                foreach ($states as $state) {
                    if (!isset($forbidden[$state])) {
                        continue;
                    }
                    $reason = $adapter->getForbiddenReason($state);
                    break;
                }

                $this->forbiddenFields[$field_key] = $reason;
            }

            $forbidden_reason = $this->forbiddenFields[$field_key];
            if ($forbidden_reason !== null) {
                $this->newConditionTranscript($rule, $condition)
                    ->setResult(HeraldConditionTranscript::RESULT_FORBIDDEN)
                    ->setNote($forbidden_reason);

                $is_forbidden = true;
            }
        }

        foreach ($rule->getActions() as $action_record) {
            $action_key = $action_record->getAction();

            if (!isset($this->forbiddenActions[$action_key])) {
                $reason = null;

                try {
                    $states = $adapter->getRequiredActionStates($action_key);
                } catch (Exception $ex) {
                    $states = array();
                }

                foreach ($states as $state) {
                    if (!isset($forbidden[$state])) {
                        continue;
                    }
                    $reason = $adapter->getForbiddenReason($state);
                    break;
                }

                $this->forbiddenActions[$action_key] = $reason;
            }

            $forbidden_reason = $this->forbiddenActions[$action_key];
            if ($forbidden_reason !== null) {
                $this->newApplyTranscript($adapter, $rule, $action_record)
                    ->setAppliedReason(
                        array(
                            array(
                                'type' => HeraldAction::DO_STANDARD_FORBIDDEN,
                                'data' => $forbidden_reason,
                            ),
                        ));

                $is_forbidden = true;
            }
        }

        return $is_forbidden;
    }

}
