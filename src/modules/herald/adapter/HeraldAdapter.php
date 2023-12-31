<?php

namespace orangins\modules\herald\adapter;

use Exception;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\engine\exception\HeraldInvalidConditionException;
use orangins\modules\herald\engine\exception\HeraldRecursiveConditionsException;
use orangins\modules\herald\engine\HeraldEffect;
use orangins\modules\herald\engine\HeraldEngine;
use orangins\modules\herald\field\HeraldField;
use orangins\modules\herald\models\HeraldActionRecord;
use orangins\modules\herald\models\HeraldCondition;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\models\transcript\HeraldApplyTranscript;
use orangins\modules\herald\systemaction\HeraldAction;
use orangins\modules\herald\value\HeraldTokenizerFieldValue;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\handles\pool\PhabricatorHandleList;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilSafeHTML;
use ReflectionClass;
use ReflectionException;
use yii\base\UnknownPropertyException;

/**
 * Class HeraldAdapter
 * @package orangins\modules\herald\adapter
 * @author 陈妙威
 */
abstract class HeraldAdapter extends OranginsObject
{

    /**
     *
     */
    const CONDITION_CONTAINS = 'contains';
    /**
     *
     */
    const CONDITION_NOT_CONTAINS = '!contains';
    /**
     *
     */
    const CONDITION_IS = 'is';
    /**
     *
     */
    const CONDITION_IS_NOT = '!is';
    /**
     *
     */
    const CONDITION_IS_ANY = 'isany';
    /**
     *
     */
    const CONDITION_IS_NOT_ANY = '!isany';
    /**
     *
     */
    const CONDITION_INCLUDE_ALL = 'all';
    /**
     *
     */
    const CONDITION_INCLUDE_ANY = 'any';
    /**
     *
     */
    const CONDITION_INCLUDE_NONE = 'none';
    /**
     *
     */
    const CONDITION_IS_ME = 'me';
    /**
     *
     */
    const CONDITION_IS_NOT_ME = '!me';
    /**
     *
     */
    const CONDITION_REGEXP = 'regexp';
    /**
     *
     */
    const CONDITION_NOT_REGEXP = '!regexp';
    /**
     *
     */
    const CONDITION_RULE = 'conditions';
    /**
     *
     */
    const CONDITION_NOT_RULE = '!conditions';
    /**
     *
     */
    const CONDITION_EXISTS = 'exists';
    /**
     *
     */
    const CONDITION_NOT_EXISTS = '!exists';
    /**
     *
     */
    const CONDITION_UNCONDITIONALLY = 'unconditionally';
    /**
     *
     */
    const CONDITION_NEVER = 'never';
    /**
     *
     */
    const CONDITION_REGEXP_PAIR = 'regexp-pair';
    /**
     *
     */
    const CONDITION_HAS_BIT = 'bit';
    /**
     *
     */
    const CONDITION_NOT_BIT = '!bit';
    /**
     *
     */
    const CONDITION_IS_TRUE = 'true';
    /**
     *
     */
    const CONDITION_IS_FALSE = 'false';

    /**
     * @var
     */
    private $contentSource;
    /**
     * @var
     */
    private $isNewObject;
    /**
     * @var
     */
    private $applicationEmail;
    /**
     * @var array
     */
    private $appliedTransactions = array();
    /**
     * @var array
     */
    private $queuedTransactions = array();
    /**
     * @var array
     */
    private $emailPHIDs = array();
    /**
     * @var array
     */
    private $forcedEmailPHIDs = array();
    /**
     * @var
     */
    private $fieldMap;
    /**
     * @var
     */
    private $actionMap;
    /**
     * @var array
     */
    private $edgeCache = array();
    /**
     * @var array
     */
    private $forbiddenActions = array();
    /**
     * @var
     */
    private $viewer;
    /**
     * @var array
     */
    private $mustEncryptReasons = array();
    /**
     * @var
     */
    private $actingAsPHID;
    /**
     * @var array
     */
    private $webhookMap = array();

    /**
     * @return array
     * @author 陈妙威
     */
    public function getEmailPHIDs()
    {
        return array_values($this->emailPHIDs);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getForcedEmailPHIDs()
    {
        return array_values($this->forcedEmailPHIDs);
    }

    /**
     * @param $acting_as_phid
     * @return $this
     * @author 陈妙威
     */
    final public function setActingAsPHID($acting_as_phid)
    {
        $this->actingAsPHID = $acting_as_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getActingAsPHID()
    {
        return $this->actingAsPHID;
    }

    /**
     * @param $phid
     * @param $force
     * @return $this
     * @author 陈妙威
     */
    public function addEmailPHID($phid, $force)
    {
        $this->emailPHIDs[$phid] = $phid;
        if ($force) {
            $this->forcedEmailPHIDs[$phid] = $phid;
        }
        return $this;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        // See PHI276. Normally, Herald runs without regard for policy checks.
        // However, we use a real viewer during test console runs: this makes
        // intracluster calls to Diffusion APIs work even if web nodes don't
        // have privileged credentials.

        if ($this->viewer) {
            return $this->viewer;
        }

        return PhabricatorUser::getOmnipotentUser();
    }

    /**
     * @param PhabricatorContentSource $content_source
     * @return $this
     * @author 陈妙威
     */
    public function setContentSource(PhabricatorContentSource $content_source)
    {
        $this->contentSource = $content_source;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContentSource()
    {
        return $this->contentSource;
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function getIsNewObject()
    {
        if (is_bool($this->isNewObject)) {
            return $this->isNewObject;
        }

        throw new Exception(
            pht(
                'You must %s to a boolean first!',
                'setIsNewObject()'));
    }

    /**
     * @param $new
     * @return $this
     * @author 陈妙威
     */
    public function setIsNewObject($new)
    {
        $this->isNewObject = (bool)$new;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsApplicationEmail()
    {
        return false;
    }

    /**
     * @param PhabricatorMetaMTAApplicationEmail $email
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationEmail(
        PhabricatorMetaMTAApplicationEmail $email)
    {
        $this->applicationEmail = $email;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationEmail()
    {
        return $this->applicationEmail;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->getObject()->getPHID();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getHeraldName();

    /**
     * @param $field_key
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getHeraldField($field_key)
    {
        return $this->requireFieldImplementation($field_key)
            ->getHeraldFieldValue($this->getObject());
    }

    /**
     * @param array $effects
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function applyHeraldEffects(array $effects)
    {
        assert_instances_of($effects, HeraldEffect::class);

        $result = array();
        foreach ($effects as $effect) {
            $result[] = $this->applyStandardEffect($effect);
        }

        return $result;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function isAvailableToUser(PhabricatorUser $viewer)
    {
        $applications = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withInstalled(true)
            ->withShortName(false)
            ->withClasses(array($this->getAdapterApplicationClass()))
            ->execute();

        return !empty($applications);
    }


    /**
     * Set the list of transactions which just took effect.
     *
     * These transactions are set by @{class:PhabricatorApplicationEditor}
     * automatically, before it invokes Herald.
     *
     * @param array $xactions
     * @return HeraldAdapter
     */
    final public function setAppliedTransactions(array $xactions)
    {
        assert_instances_of($xactions, PhabricatorApplicationTransaction::className());
        $this->appliedTransactions = $xactions;
        return $this;
    }


    /**
     * Get a list of transactions which just took effect.
     *
     * When an object is edited normally, transactions are applied and then
     * Herald executes. You can call this method to examine the transactions
     * if you want to react to them.
     *
     * @return array<PhabricatorApplicationTransaction> List of transactions.
     */
    final public function getAppliedTransactions()
    {
        return $this->appliedTransactions;
    }

    /**
     * @param PhabricatorApplicationTransaction $transaction
     * @author 陈妙威
     */
    final public function queueTransaction(
        PhabricatorApplicationTransaction $transaction)
    {
        $this->queuedTransactions[] = $transaction;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getQueuedTransactions()
    {
        return $this->queuedTransactions;
    }

    /**
     * @return PhabricatorApplicationTransaction
     * @throws Exception
     * @author 陈妙威
     */
    final public function newTransaction()
    {
        $object = $this->newObject();

        if (!($object instanceof PhabricatorApplicationTransactionInterface)) {
            throw new Exception(
                pht(
                    'Unable to build a new transaction for adapter object; it does ' .
                    'not implement "%s".',
                    'PhabricatorApplicationTransactionInterface'));
        }

        $xaction = $object->getApplicationTransactionTemplate();

        if (!($xaction instanceof PhabricatorApplicationTransaction)) {
            throw new Exception(
                pht(
                    'Expected object (of class "%s") to return a transaction template ' .
                    '(of class "%s"), but it returned something else ("%s").',
                    get_class($object),
                    'PhabricatorApplicationTransaction',
                    phutil_describe_type($xaction)));
        }

        return $xaction;
    }


    /**
     * NOTE: You generally should not override this; it exists to support legacy
     * adapters which had hard-coded content types.
     * @throws ReflectionException
     */
    public function getAdapterContentType()
    {
        return (new ReflectionClass(get_called_class()))->getShortName();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAdapterContentName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAdapterContentDescription();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAdapterApplicationClass();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getObject();

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function setObject($object);

    /**
     * Return a new characteristic object for this adapter.
     *
     * The adapter will use this object to test for interfaces, generate
     * transactions, and interact with custom fields.
     *
     * Adapters must return an object from this method to enable custom
     * field rules and various implicit actions.
     *
     * Normally, you'll return an empty version of the adapted object:
     *
     *   return new ApplicationObject();
     *
     * @return null|object Template object.
     */
    protected function newObject()
    {
        return null;
    }

    /**
     * @param $rule_type
     * @return bool
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        return false;
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canTriggerOnObject($object)
    {
        return false;
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function isTestAdapterForObject($object)
    {
        return false;
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canCreateTestAdapterForObject($object)
    {
        return $this->isTestAdapterForObject($object);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function newTestAdapter(PhabricatorUser $viewer, $object)
    {
        /** @var HeraldAdapter $wild */
        $wild = id(clone $this);
        return $wild
            ->setObject($object);
    }

    /**
     * @return |null
     * @author 陈妙威
     */
    public function getAdapterTestDescription()
    {
        return null;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function explainValidTriggerObjects()
    {
        return pht('This adapter can not trigger on objects.');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTriggerObjectPHIDs()
    {
        return array($this->getPHID());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAdapterSortKey()
    {
        return sprintf(
            '%08d%s',
            $this->getAdapterSortOrder(),
            $this->getAdapterContentName());
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getAdapterSortOrder()
    {
        return 1000;
    }


    /* -(  Fields  )------------------------------------------------------------- */

    /**
     * @return HeraldField[]
     * @throws Exception
     * @author 陈妙威
     */
    private function getFieldImplementationMap()
    {
        if ($this->fieldMap === null) {
            // We can't use PhutilClassMapQuery here because field expansion
            // depends on the adapter and object.

            $object = $this->getObject();

            $map = array();
            $all = HeraldField::getAllFields();
            foreach ($all as $key => $field) {
                /** @var  $x */
                $x = clone $field;
                /** @var HeraldField $field */
                $field = $x->setAdapter($this);

                if (!$field->supportsObject($object)) {
                    continue;
                }
                $subfields = $field->getFieldsForObject($object);
                foreach ($subfields as $subkey => $subfield) {
                    if (isset($map[$subkey])) {
                        throw new Exception(
                            pht(
                                'Two HeraldFields (of classes "%s" and "%s") have the same ' .
                                'field key ("%s") after expansion for an object of class ' .
                                '"%s" inside adapter "%s". Each field must have a unique ' .
                                'field key.',
                                get_class($subfield),
                                get_class($map[$subkey]),
                                $subkey,
                                get_class($object),
                                get_class($this)));
                    }

                    /** @var HeraldField $x1 */
                    $x1 = clone $subfield;
                    $subfield = $x1->setAdapter($this);

                    $map[$subkey] = $subfield;
                }
            }
            $this->fieldMap = $map;
        }

        return $this->fieldMap;
    }

    /**
     * @param $key
     * @return HeraldField
     * @throws Exception
     * @author 陈妙威
     */
    private function getFieldImplementation($key)
    {
        return idx($this->getFieldImplementationMap(), $key);
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getFields()
    {
        return array_keys($this->getFieldImplementationMap());
    }

    /**
     * @return HeraldField[]
     * @throws Exception
     * @author 陈妙威
     */
    public function getFieldNameMap()
    {
        return mpull($this->getFieldImplementationMap(), 'getHeraldFieldName');
    }

    /**
     * @param $field_key
     * @return |null
     * @throws Exception
     * @author 陈妙威
     */
    public function getFieldGroupKey($field_key)
    {
        $field = $this->getFieldImplementation($field_key);

        if (!$field) {
            return null;
        }

        return $field->getFieldGroupKey();
    }


    /* -(  Conditions  )--------------------------------------------------------- */


    /**
     * @return array
     * @author 陈妙威
     */
    public function getConditionNameMap()
    {
        return array(
            self::CONDITION_CONTAINS => pht('contains'),
            self::CONDITION_NOT_CONTAINS => pht('does not contain'),
            self::CONDITION_IS => pht('is'),
            self::CONDITION_IS_NOT => pht('is not'),
            self::CONDITION_IS_ANY => pht('is any of'),
            self::CONDITION_IS_TRUE => pht('is true'),
            self::CONDITION_IS_FALSE => pht('is false'),
            self::CONDITION_IS_NOT_ANY => pht('is not any of'),
            self::CONDITION_INCLUDE_ALL => pht('include all of'),
            self::CONDITION_INCLUDE_ANY => pht('include any of'),
            self::CONDITION_INCLUDE_NONE => pht('do not include'),
            self::CONDITION_IS_ME => pht('is myself'),
            self::CONDITION_IS_NOT_ME => pht('is not myself'),
            self::CONDITION_REGEXP => pht('matches regexp'),
            self::CONDITION_NOT_REGEXP => pht('does not match regexp'),
            self::CONDITION_RULE => pht('matches:'),
            self::CONDITION_NOT_RULE => pht('does not match:'),
            self::CONDITION_EXISTS => pht('exists'),
            self::CONDITION_NOT_EXISTS => pht('does not exist'),
            self::CONDITION_UNCONDITIONALLY => '',  // don't show anything!
            self::CONDITION_NEVER => '',  // don't show anything!
            self::CONDITION_REGEXP_PAIR => pht('matches regexp pair'),
            self::CONDITION_HAS_BIT => pht('has bit'),
            self::CONDITION_NOT_BIT => pht('lacks bit'),
        );
    }

    /**
     * @param $field
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getConditionsForField($field)
    {
        return $this->requireFieldImplementation($field)
            ->getHeraldFieldConditions();
    }

    /**
     * @param $field_key
     * @return HeraldField
     * @throws Exception
     * @author 陈妙威
     */
    private function requireFieldImplementation($field_key)
    {
        $field = $this->getFieldImplementation($field_key);

        if (!$field) {
            throw new Exception(
                pht(
                    'No field with key "%s" is available to Herald adapter "%s".',
                    $field_key,
                    get_class($this)));
        }

        return $field;
    }

    /**
     * @param HeraldEngine $engine
     * @param HeraldRule $rule
     * @param HeraldCondition $condition
     * @param $field_value
     * @return bool|false|int
     * @throws HeraldInvalidConditionException
     * @throws HeraldRecursiveConditionsException
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws UnknownPropertyException
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function doesConditionMatch(
        HeraldEngine $engine,
        HeraldRule $rule,
        HeraldCondition $condition,
        $field_value)
    {

        $condition_type = $condition->getFieldCondition();
        $condition_value = $condition->getValue();

        switch ($condition_type) {
            case self::CONDITION_CONTAINS:
            case self::CONDITION_NOT_CONTAINS:
                // "Contains and "does not contain" can take an array of strings, as in
                // "Any changed filename" for diffs.

                $result_if_match = ($condition_type == self::CONDITION_CONTAINS);

                foreach ((array)$field_value as $value) {
                    if (stripos($value, $condition_value) !== false) {
                        return $result_if_match;
                    }
                }
                return !$result_if_match;
            case self::CONDITION_IS:
                return ($field_value == $condition_value);
            case self::CONDITION_IS_NOT:
                return ($field_value != $condition_value);
            case self::CONDITION_IS_ME:
                return ($field_value == $rule->getAuthorPHID());
            case self::CONDITION_IS_NOT_ME:
                return ($field_value != $rule->getAuthorPHID());
            case self::CONDITION_IS_ANY:
                if (!is_array($condition_value)) {
                    throw new HeraldInvalidConditionException(
                        pht('Expected condition value to be an array.'));
                }
                $condition_value = array_fuse($condition_value);
                return isset($condition_value[$field_value]);
            case self::CONDITION_IS_NOT_ANY:
                if (!is_array($condition_value)) {
                    throw new HeraldInvalidConditionException(
                        pht('Expected condition value to be an array.'));
                }
                $condition_value = array_fuse($condition_value);
                return !isset($condition_value[$field_value]);
            case self::CONDITION_INCLUDE_ALL:
                if (!is_array($field_value)) {
                    throw new HeraldInvalidConditionException(
                        pht('Object produced non-array value!'));
                }
                if (!is_array($condition_value)) {
                    throw new HeraldInvalidConditionException(
                        pht('Expected condition value to be an array.'));
                }

                $have = array_select_keys(array_fuse($field_value), $condition_value);
                return (count($have) == count($condition_value));
            case self::CONDITION_INCLUDE_ANY:
                return (bool)array_select_keys(
                    array_fuse($field_value),
                    $condition_value);
            case self::CONDITION_INCLUDE_NONE:
                return !array_select_keys(
                    array_fuse($field_value),
                    $condition_value);
            case self::CONDITION_EXISTS:
            case self::CONDITION_IS_TRUE:
                return (bool)$field_value;
            case self::CONDITION_NOT_EXISTS:
            case self::CONDITION_IS_FALSE:
                return !$field_value;
            case self::CONDITION_UNCONDITIONALLY:
                return (bool)$field_value;
            case self::CONDITION_NEVER:
                return false;
            case self::CONDITION_REGEXP:
            case self::CONDITION_NOT_REGEXP:
                $result_if_match = ($condition_type == self::CONDITION_REGEXP);

                foreach ((array)$field_value as $value) {
                    // We add the 'S' flag because we use the regexp multiple times.
                    // It shouldn't cause any troubles if the flag is already there
                    // - /.*/S is evaluated same as /.*/SS.
                    $result = @preg_match($condition_value . 'S', $value);
                    if ($result === false) {
                        throw new HeraldInvalidConditionException(
                            pht(
                                'Regular expression "%s" in Herald rule "%s" is not valid, ' .
                                'or exceeded backtracking or recursion limits while ' .
                                'executing. Verify the expression and correct it or rewrite ' .
                                'it with less backtracking.',
                                $condition_value,
                                $rule->getMonogram()));
                    }
                    if ($result) {
                        return $result_if_match;
                    }
                }
                return !$result_if_match;
            case self::CONDITION_REGEXP_PAIR:
                // Match a JSON-encoded pair of regular expressions against a
                // dictionary. The first regexp must match the dictionary key, and the
                // second regexp must match the dictionary value. If any key/value pair
                // in the dictionary matches both regexps, the condition is satisfied.
                $regexp_pair = null;
                try {
                    $regexp_pair = phutil_json_decode($condition_value);
                } catch (PhutilJSONParserException $ex) {
                    throw new HeraldInvalidConditionException(
                        pht('Regular expression pair is not valid JSON!'));
                }
                if (count($regexp_pair) != 2) {
                    throw new HeraldInvalidConditionException(
                        pht('Regular expression pair is not a pair!'));
                }

                $key_regexp = array_shift($regexp_pair);
                $value_regexp = array_shift($regexp_pair);

                foreach ((array)$field_value as $key => $value) {
                    $key_matches = @preg_match($key_regexp, $key);
                    if ($key_matches === false) {
                        throw new HeraldInvalidConditionException(
                            pht('First regular expression is invalid!'));
                    }
                    if ($key_matches) {
                        $value_matches = @preg_match($value_regexp, $value);
                        if ($value_matches === false) {
                            throw new HeraldInvalidConditionException(
                                pht('Second regular expression is invalid!'));
                        }
                        if ($value_matches) {
                            return true;
                        }
                    }
                }
                return false;
            case self::CONDITION_RULE:
            case self::CONDITION_NOT_RULE:
                $rule = $engine->getRule($condition_value);
                if (!$rule) {
                    throw new HeraldInvalidConditionException(
                        pht('Condition references a rule which does not exist!'));
                }

                $is_not = ($condition_type == self::CONDITION_NOT_RULE);
                $result = $engine->doesRuleMatch($rule, $this);
                if ($is_not) {
                    $result = !$result;
                }
                return $result;
            case self::CONDITION_HAS_BIT:
                return (($condition_value & $field_value) === (int)$condition_value);
            case self::CONDITION_NOT_BIT:
                return (($condition_value & $field_value) !== (int)$condition_value);
            default:
                throw new HeraldInvalidConditionException(
                    pht("Unknown condition '%s'.", $condition_type));
        }
    }

    /**
     * @param HeraldCondition $condition
     * @throws HeraldInvalidConditionException
     * @author 陈妙威
     */
    public function willSaveCondition(HeraldCondition $condition)
    {
        $condition_type = $condition->getFieldCondition();
        $condition_value = $condition->getValue();

        switch ($condition_type) {
            case self::CONDITION_REGEXP:
            case self::CONDITION_NOT_REGEXP:
                $ok = @preg_match($condition_value, '');
                if ($ok === false) {
                    throw new HeraldInvalidConditionException(
                        pht(
                            'The regular expression "%s" is not valid. Regular expressions ' .
                            'must have enclosing characters (e.g. "@/path/to/file@", not ' .
                            '"/path/to/file") and be syntactically correct.',
                            $condition_value));
                }
                break;
            case self::CONDITION_REGEXP_PAIR:
                $json = null;
                try {
                    $json = phutil_json_decode($condition_value);
                } catch (PhutilJSONParserException $ex) {
                    throw new HeraldInvalidConditionException(
                        pht(
                            'The regular expression pair "%s" is not valid JSON. Enter a ' .
                            'valid JSON array with two elements.',
                            $condition_value));
                }

                if (count($json) != 2) {
                    throw new HeraldInvalidConditionException(
                        pht(
                            'The regular expression pair "%s" must have exactly two ' .
                            'elements.',
                            $condition_value));
                }

                $key_regexp = array_shift($json);
                $val_regexp = array_shift($json);

                $key_ok = @preg_match($key_regexp, '');
                if ($key_ok === false) {
                    throw new HeraldInvalidConditionException(
                        pht(
                            'The first regexp in the regexp pair, "%s", is not a valid ' .
                            'regexp.',
                            $key_regexp));
                }

                $val_ok = @preg_match($val_regexp, '');
                if ($val_ok === false) {
                    throw new HeraldInvalidConditionException(
                        pht(
                            'The second regexp in the regexp pair, "%s", is not a valid ' .
                            'regexp.',
                            $val_regexp));
                }
                break;
            case self::CONDITION_CONTAINS:
            case self::CONDITION_NOT_CONTAINS:
            case self::CONDITION_IS:
            case self::CONDITION_IS_NOT:
            case self::CONDITION_IS_ANY:
            case self::CONDITION_IS_NOT_ANY:
            case self::CONDITION_INCLUDE_ALL:
            case self::CONDITION_INCLUDE_ANY:
            case self::CONDITION_INCLUDE_NONE:
            case self::CONDITION_IS_ME:
            case self::CONDITION_IS_NOT_ME:
            case self::CONDITION_RULE:
            case self::CONDITION_NOT_RULE:
            case self::CONDITION_EXISTS:
            case self::CONDITION_NOT_EXISTS:
            case self::CONDITION_UNCONDITIONALLY:
            case self::CONDITION_NEVER:
            case self::CONDITION_HAS_BIT:
            case self::CONDITION_NOT_BIT:
            case self::CONDITION_IS_TRUE:
            case self::CONDITION_IS_FALSE:
                // No explicit validation for these types, although there probably
                // should be in some cases.
                break;
            default:
                throw new HeraldInvalidConditionException(
                    pht(
                        'Unknown condition "%s"!',
                        $condition_type));
        }
    }


    /* -(  Actions  )------------------------------------------------------------ */

    /**
     * @return array|null
     * @throws Exception
     * @author 陈妙威
     */
    private function getActionImplementationMap()
    {
        if ($this->actionMap === null) {
            // We can't use PhutilClassMapQuery here because action expansion
            // depends on the adapter and object.

            $object = $this->getObject();

            $map = array();
            $all = HeraldAction::getAllActions();
            foreach ($all as $key => $action) {
                /** @var HeraldAction $x */
                $x = clone $action;
                $action = $x->setAdapter($this);

                if (!$action->supportsObject($object)) {
                    continue;
                }

                $subactions = $action->getActionsForObject($object);
                foreach ($subactions as $subkey => $subaction) {
                    if (isset($map[$subkey])) {
                        throw new Exception(
                            pht(
                                'Two HeraldActions (of classes "%s" and "%s") have the same ' .
                                'action key ("%s") after expansion for an object of class ' .
                                '"%s" inside adapter "%s". Each action must have a unique ' .
                                'action key.',
                                get_class($subaction),
                                get_class($map[$subkey]),
                                $subkey,
                                get_class($object),
                                get_class($this)));
                    }

                    /** @var HeraldAction $x1 */
                    $x1 = clone $subaction;
                    $subaction = $x1->setAdapter($this);

                    $map[$subkey] = $subaction;
                }
            }
            $this->actionMap = $map;
        }

        return $this->actionMap;
    }

    /**
     * @param $action_key
     * @return object
     * @throws Exception
     * @author 陈妙威
     */
    private function requireActionImplementation($action_key)
    {
        $action = $this->getActionImplementation($action_key);

        if (!$action) {
            throw new Exception(
                pht(
                    'No action with key "%s" is available to Herald adapter "%s".',
                    $action_key,
                    get_class($this)));
        }

        return $action;
    }

    /**
     * @param $rule_type
     * @return array|null
     * @throws Exception
     * @author 陈妙威
     */
    private function getActionsForRuleType($rule_type)
    {
        $actions = $this->getActionImplementationMap();

        foreach ($actions as $key => $action) {
            if (!$action->supportsRuleType($rule_type)) {
                unset($actions[$key]);
            }
        }

        return $actions;
    }

    /**
     * @param $key
     * @return object
     * @throws Exception
     * @author 陈妙威
     */
    public function getActionImplementation($key)
    {
        return idx($this->getActionImplementationMap(), $key);
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getActionKeys()
    {
        return array_keys($this->getActionImplementationMap());
    }

    /**
     * @param $action_key
     * @return |null
     * @throws Exception
     * @author 陈妙威
     */
    public function getActionGroupKey($action_key)
    {
        $action = $this->getActionImplementation($action_key);
        if (!$action) {
            return null;
        }

        return $action->getActionGroupKey();
    }

    /**
     * @param $rule_type
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getActions($rule_type)
    {
        $actions = array();
        foreach ($this->getActionsForRuleType($rule_type) as $key => $action) {
            $actions[] = $key;
        }

        return $actions;
    }

    /**
     * @param $rule_type
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getActionNameMap($rule_type)
    {
        $map = array();
        foreach ($this->getActionsForRuleType($rule_type) as $key => $action) {
            $map[$key] = $action->getHeraldActionName();
        }

        return $map;
    }

    /**
     * @param HeraldRule $rule
     * @param HeraldActionRecord $action
     * @throws Exception
     * @author 陈妙威
     */
    public function willSaveAction(
        HeraldRule $rule,
        HeraldActionRecord $action)
    {

        $impl = $this->requireActionImplementation($action->getAction());
        $target = $action->getTarget();
        $target = $impl->willSaveActionValue($target);

        $action->setTarget($target);
    }


    /* -(  Values  )------------------------------------------------------------- */


    /**
     * @param $field
     * @param $condition
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getValueTypeForFieldAndCondition($field, $condition)
    {
        return $this->requireFieldImplementation($field)
            ->getHeraldFieldValueType($condition);
    }

    /**
     * @param $action
     * @param $rule_type
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getValueTypeForAction($action, $rule_type)
    {
        $impl = $this->requireActionImplementation($action);
        return $impl->getHeraldActionValueType();
    }

    /**
     * @param PhabricatorTypeaheadDatasource $datasource
     * @return mixed
     * @author 陈妙威
     */
    private function buildTokenizerFieldValue(
        PhabricatorTypeaheadDatasource $datasource)
    {

        $key = 'action.' . get_class($datasource);

        return (new HeraldTokenizerFieldValue())
            ->setKey($key)
            ->setDatasource($datasource);
    }

    /* -(  Repetition  )--------------------------------------------------------- */


    /**
     * @return array
     * @author 陈妙威
     */
    public function getRepetitionOptions()
    {
        $options = array();

        $options[] = HeraldRule::REPEAT_EVERY;

        // Some rules, like pre-commit rules, only ever fire once. It doesn't
        // make sense to use state-based repetition policies like "only the first
        // time" for these rules.

        if (!$this->isSingleEventAdapter()) {
            $options[] = HeraldRule::REPEAT_FIRST;
            $options[] = HeraldRule::REPEAT_CHANGE;
        }

        return $options;
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    protected function initializeNewAdapter()
    {
        $this->setObject($this->newObject());
        return $this;
    }

    /**
     * Does this adapter's event fire only once?
     *
     * Single use adapters (like pre-commit and diff adapters) only fire once,
     * so fields like "Is new object" don't make sense to apply to their content.
     *
     * @return bool
     */
    public function isSingleEventAdapter()
    {
        return false;
    }

    /**
     * @return HeraldAdapter[]
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function getAllAdapters()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getAdapterContentType')
            ->setSortMethod('getAdapterSortKey')
            ->execute();
    }

    /**
     * @param $content_type
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    public static function getAdapterForContentType($content_type)
    {
        $adapters = self::getAllAdapters();

        foreach ($adapters as $adapter) {
            if ($adapter->getAdapterContentType() == $content_type) {
                /** @var HeraldAdapter $adapter */
                $adapter = id(clone $adapter);
                $adapter->initializeNewAdapter();
                return $adapter;
            }
        }

        throw new Exception(
            pht(
                'No adapter exists for Herald content type "%s".',
                $content_type));
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    public static function getEnabledAdapterMap(PhabricatorUser $viewer)
    {
        $map = array();

        $adapters = self::getAllAdapters();
        foreach ($adapters as $adapter) {
            if (!$adapter->isAvailableToUser($viewer)) {
                continue;
            }
            $type = $adapter->getAdapterContentType();
            $name = $adapter->getAdapterContentName();
            $map[$type] = $name;
        }

        return $map;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param HeraldCondition $condition
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getEditorValueForCondition(
        PhabricatorUser $viewer,
        HeraldCondition $condition)
    {

        $field = $this->requireFieldImplementation($condition->getFieldName());

        return $field->getEditorValue(
            $viewer,
            $condition->getFieldCondition(),
            $condition->getValue());
    }

    /**
     * @param PhabricatorUser $viewer
     * @param HeraldActionRecord $action_record
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getEditorValueForAction(
        PhabricatorUser $viewer,
        HeraldActionRecord $action_record)
    {

        $action = $this->requireActionImplementation($action_record->getAction());

        return $action->getEditorValue(
            $viewer,
            $action_record->getTarget());
    }

    /**
     * @param HeraldRule $rule
     * @param PhabricatorHandleList $handles
     * @param PhabricatorUser $viewer
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function renderRuleAsText(
        HeraldRule $rule,
        PhabricatorHandleList $handles,
        PhabricatorUser $viewer)
    {

//        require_celerity_resource('herald-css');

        $icon = (new PHUIIconView())
            ->setIcon('fa-chevron-circle-right lightgreytext mr-1')
            ->addClass('herald-list-icon');

        if ($rule->getMustMatchAll()) {
            $match_text = pht('When all of these conditions are met:');
        } else {
            $match_text = pht('When any of these conditions are met:');
        }

        $match_title = phutil_tag(
            'p',
            array(
                'class' => 'herald-list-description',
            ),
            $match_text);

        $match_list = array();
        foreach ($rule->getConditions() as $condition) {
            $match_list[] = phutil_tag(
                'div',
                array(
                    'class' => 'herald-list-item',
                ),
                array(
                    $icon,
                    $this->renderConditionAsText($condition, $handles, $viewer),
                ));
        }

        if ($rule->isRepeatFirst()) {
            $action_text = pht(
                'Take these actions the first time this rule matches:');
        } else if ($rule->isRepeatOnChange()) {
            $action_text = pht(
                'Take these actions if this rule did not match the last time:');
        } else {
            $action_text = pht(
                'Take these actions every time this rule matches:');
        }

        $action_title = phutil_tag(
            'p',
            array(
                'class' => 'herald-list-description',
            ),
            $action_text);

        $action_list = array();
        foreach ($rule->getActions() as $action) {
            $action_list[] = phutil_tag(
                'div',
                array(
                    'class' => 'herald-list-item',
                ),
                array(
                    $icon,
                    $this->renderActionAsText($viewer, $action, $handles),
                ));
        }

        return array(
            $match_title,
            $match_list,
            $action_title,
            $action_list,
        );
    }

    /**
     * @param HeraldCondition $condition
     * @param PhabricatorHandleList $handles
     * @param PhabricatorUser $viewer
     * @return array|string
     * @throws Exception
     * @author 陈妙威
     */
    private function renderConditionAsText(
        HeraldCondition $condition,
        PhabricatorHandleList $handles,
        PhabricatorUser $viewer)
    {

        $field_type = $condition->getFieldName();
        $field = $this->getFieldImplementation($field_type);

        if (!$field) {
            return pht('Unknown Field: "%s"', $field_type);
        }

        $field_name = $field->getHeraldFieldName();

        $condition_type = $condition->getFieldCondition();
        $condition_name = idx($this->getConditionNameMap(), $condition_type);

        $value = $this->renderConditionValueAsText($condition, $handles, $viewer);

        return array(
            $field_name,
            ' ',
            $condition_name,
            ' ',
            $value,
        );
    }

    /**
     * @param PhabricatorUser $viewer
     * @param HeraldActionRecord $action
     * @param PhabricatorHandleList $handles
     * @return PhutilSafeHTML
     * @throws Exception
     * @author 陈妙威
     */
    private function renderActionAsText(
        PhabricatorUser $viewer,
        HeraldActionRecord $action,
        PhabricatorHandleList $handles)
    {

        $impl = $this->getActionImplementation($action->getAction());
        if ($impl) {
            $impl->setViewer($viewer);

            $value = $action->getTarget();
            return $impl->renderActionDescription($value);
        }

        $rule_global = HeraldRuleTypeConfig::RULE_TYPE_GLOBAL;

        $action_type = $action->getAction();

        $default = pht('(Unknown Action "%s") equals', $action_type);

        $action_name = idx(
            $this->getActionNameMap($rule_global),
            $action_type,
            $default);

        $target = $this->renderActionTargetAsText($action, $handles);

        return hsprintf('    %s %s', $action_name, $target);
    }

    /**
     * @param HeraldCondition $condition
     * @param PhabricatorHandleList $handles
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function renderConditionValueAsText(
        HeraldCondition $condition,
        PhabricatorHandleList $handles,
        PhabricatorUser $viewer)
    {

        $field = $this->requireFieldImplementation($condition->getFieldName());

        return $field->renderConditionValue(
            $viewer,
            $condition->getFieldCondition(),
            $condition->getValue());
    }

    /**
     * @param HeraldActionRecord $action
     * @param PhabricatorHandleList $handles
     * @return array|PhutilSafeHTML
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function renderActionTargetAsText(
        HeraldActionRecord $action,
        PhabricatorHandleList $handles)
    {

        // TODO: This should be driven through HeraldAction.

        $target = $action->getTarget();
        if (!is_array($target)) {
            $target = array($target);
        }
        foreach ($target as $index => $val) {
            switch ($action->getAction()) {
                default:
                    $handle = $handles->getHandleIfExists($val);
                    if ($handle) {
                        $target[$index] = $handle->renderLink();
                    }
                    break;
            }
        }
        $target = phutil_implode_html(', ', $target);
        return $target;
    }

    /**
     * Given a @{class:HeraldRule}, this function extracts all the phids that
     * we'll want to load as handles later.
     *
     * This function performs a somewhat hacky approach to figuring out what
     * is and is not a phid - try to get the phid type and if the type is
     * *not* unknown assume its a valid phid.
     *
     * Don't try this at home. Use more strongly typed data at home.
     *
     * Think of the children.
     * @param HeraldRule $rule
     * @return array
     * @throws UnknownPropertyException
     */
    public static function getHandlePHIDs(HeraldRule $rule)
    {
        $phids = array($rule->getAuthorPHID());
        foreach ($rule->getConditions() as $condition) {
            $value = $condition->getValue();
            if (!is_array($value)) {
                $value = array($value);
            }
            foreach ($value as $val) {
                if (PhabricatorPHID::phid_get_type($val) !=
                    PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN) {
                    $phids[] = $val;
                }
            }
        }

        
        foreach ($rule->getActions() as $action) {
            $target = $action->getTarget();
            if (!is_array($target)) {
                $target = array($target);
            }
            foreach ($target as $val) {
                if (PhabricatorPHID::phid_get_type($val) !=
                    PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN) {
                    $phids[] = $val;
                }
            }
        }

        if ($rule->isObjectRule()) {
            $phids[] = $rule->getTriggerObjectPHID();
        }

        return $phids;
    }

    /* -(  Applying Effects  )--------------------------------------------------- */


    /**
     * @task apply
     * @param HeraldEffect $effect
     * @return HeraldApplyTranscript
     * @throws Exception
     */
    protected function applyStandardEffect(HeraldEffect $effect)
    {
        $action = $effect->getAction();
        $rule_type = $effect->getRule()->getRuleType();

        $impl = $this->getActionImplementation($action);
        if (!$impl) {
            return new HeraldApplyTranscript(
                $effect,
                false,
                array(
                    array(
                        HeraldAction::DO_STANDARD_INVALID_ACTION,
                        $action,
                    ),
                ));
        }

        if (!$impl->supportsRuleType($rule_type)) {
            return new HeraldApplyTranscript(
                $effect,
                false,
                array(
                    array(
                        HeraldAction::DO_STANDARD_WRONG_RULE_TYPE,
                        $rule_type,
                    ),
                ));
        }

        $impl->applyEffect($this->getObject(), $effect);
        return $impl->getApplyTranscript($effect);
    }

    /**
     * @param $type
     * @return mixed
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function loadEdgePHIDs($type)
    {
        if (!isset($this->edgeCache[$type])) {
            $phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
                $this->getObject()->getPHID(),
                $type);

            $this->edgeCache[$type] = array_fuse($phids);
        }
        return $this->edgeCache[$type];
    }


    /* -(  Forbidden Actions  )-------------------------------------------------- */


    /**
     * @return array
     * @author 陈妙威
     */
    final public function getForbiddenActions()
    {
        return array_keys($this->forbiddenActions);
    }

    /**
     * @param $action
     * @param $reason
     * @return $this
     * @author 陈妙威
     */
    final public function setForbiddenAction($action, $reason)
    {
        $this->forbiddenActions[$action] = $reason;
        return $this;
    }

    /**
     * @param $field_key
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getRequiredFieldStates($field_key)
    {
        return $this->requireFieldImplementation($field_key)
            ->getRequiredAdapterStates();
    }

    /**
     * @param $action_key
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getRequiredActionStates($action_key)
    {
        return $this->requireActionImplementation($action_key)
            ->getRequiredAdapterStates();
    }

    /**
     * @param $action
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getForbiddenReason($action)
    {
        if (!isset($this->forbiddenActions[$action])) {
            throw new Exception(
                pht(
                    'Action "%s" is not forbidden!',
                    $action));
        }

        return $this->forbiddenActions[$action];
    }


    /* -(  Must Encrypt  )------------------------------------------------------- */


    /**
     * @param $reason
     * @return $this
     * @author 陈妙威
     */
    final public function addMustEncryptReason($reason)
    {
        $this->mustEncryptReasons[] = $reason;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getMustEncryptReasons()
    {
        return $this->mustEncryptReasons;
    }


    /* -(  Webhooks  )----------------------------------------------------------- */


    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsWebhooks()
    {
        return true;
    }


    /**
     * @param $webhook_phid
     * @param $rule_phid
     * @return $this
     * @author 陈妙威
     */
    final public function queueWebhook($webhook_phid, $rule_phid)
    {
        $this->webhookMap[$webhook_phid][] = $rule_phid;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getWebhookMap()
    {
        return $this->webhookMap;
    }

}
