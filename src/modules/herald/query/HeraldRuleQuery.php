<?php

namespace orangins\modules\herald\query;


use AphrontAccessDeniedQueryException;
use AphrontDatabaseConnection;
use Exception;
use orangins\lib\infrastructure\edges\constants\PhabricatorEdgeConfig;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\application\PhabricatorHeraldApplication;
use orangins\modules\herald\edge\HeraldRuleActionAffectsObjectEdgeType;
use orangins\modules\herald\models\HeraldActionRecord;
use orangins\modules\herald\models\HeraldCondition;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\models\HeraldRuleapplied;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * This is the ActiveQuery class for [[\orangins\modules\herald\models\HeraldRule]].
 *
 * @see \orangins\modules\herald\models\HeraldRule
 */
class HeraldRuleQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $authorPHIDs;
    /**
     * @var
     */
    private $ruleTypes;
    /**
     * @var
     */
    private $contentTypes;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $active;
    /**
     * @var
     */
    private $datasourceQuery;
    /**
     * @var
     */
    private $triggerObjectPHIDs;
    /**
     * @var
     */
    private $affectedObjectPHIDs;

    /**
     * @var
     */
    private $needConditionsAndActions;
    /**
     * @var
     */
    private $needAppliedToPHIDs;
    /**
     * @var
     */
    private $needValidateAuthors;

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param array $author_phids
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $author_phids)
    {
        $this->authorPHIDs = $author_phids;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withRuleTypes(array $types)
    {
        $this->ruleTypes = $types;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withContentTypes(array $types)
    {
        $this->contentTypes = $types;
        return $this;
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function withDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @param $active
     * @return $this
     * @author 陈妙威
     */
    public function withActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param $query
     * @return $this
     * @author 陈妙威
     */
    public function withDatasourceQuery($query)
    {
        $this->datasourceQuery = $query;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withTriggerObjectPHIDs(array $phids)
    {
        $this->triggerObjectPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withAffectedObjectPHIDs(array $phids)
    {
        $this->affectedObjectPHIDs = $phids;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needConditionsAndActions($need)
    {
        $this->needConditionsAndActions = $need;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function needAppliedToPHIDs(array $phids)
    {
        $this->needAppliedToPHIDs = $phids;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needValidateAuthors($need)
    {
        $this->needValidateAuthors = $need;
        return $this;
    }

    /**
     * @return HeraldRule|null
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new HeraldRule();
    }


    /**
     * @return ActiveRecord[]
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @param array $rules
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    protected function willFilterPage(array $rules)
    {
        $rule_ids = mpull($rules, 'getID');

        // Filter out any rules that have invalid adapters, or have adapters the
        // viewer isn't permitted to see or use (for example, Differential rules
        // if the user can't use Differential or Differential is not installed).
        $types = HeraldAdapter::getEnabledAdapterMap($this->getViewer());
        foreach ($rules as $key => $rule) {
            if (empty($types[$rule->getContentType()])) {
                $this->didRejectResult($rule);
                unset($rules[$key]);
            }
        }

        if ($this->needValidateAuthors || ($this->active !== null)) {
            $this->validateRuleAuthors($rules);
        }

        if ($this->active !== null) {
            $need_active = (bool)$this->active;
            foreach ($rules as $key => $rule) {
                if ($rule->getIsDisabled()) {
                    $is_active = false;
                } else if (!$rule->hasValidAuthor()) {
                    $is_active = false;
                } else {
                    $is_active = true;
                }

                if ($is_active != $need_active) {
                    unset($rules[$key]);
                }
            }
        }

        if (!$rules) {
            return array();
        }

        if ($this->needConditionsAndActions) {
            $conditions = HeraldCondition::find()->andWhere(['IN', 'rule_id', $rule_ids])->all();
            $conditions = mgroup($conditions, 'getRuleID');
            $actions = HeraldActionRecord::find()->andWhere(['IN', 'rule_id', $rule_ids])->all();
            $actions = mgroup($actions, 'getRuleID');

            foreach ($rules as $rule) {
                $rule->attachActions(idx($actions, $rule->getID(), array()));
                $rule->attachConditions(idx($conditions, $rule->getID(), array()));
            }
        }

        if ($this->needAppliedToPHIDs) {
            $applied = HeraldRuleapplied::find()
                ->andWhere(['IN', 'rule_id', $rule_ids])
                ->andWhere(['IN', 'phid', $this->needAppliedToPHIDs])
                ->all();

            $map = array();
            foreach ($applied as $row) {
                $map[$row['ruleID']][$row['phid']] = true;
            }

            foreach ($rules as $rule) {
                foreach ($this->needAppliedToPHIDs as $phid) {
                    $rule->setRuleApplied(
                        $phid,
                        isset($map[$rule->getID()][$phid]));
                }
            }
        }

        $object_phids = array();
        foreach ($rules as $rule) {
            if ($rule->isObjectRule()) {
                $object_phids[] = $rule->getTriggerObjectPHID();
            }
        }

        if ($object_phids) {
            $objects = (new PhabricatorObjectQuery())
                ->setParentQuery($this)
                ->setViewer($this->getViewer())
                ->withPHIDs($object_phids)
                ->execute();
            $objects = mpull($objects, null, 'getPHID');
        } else {
            $objects = array();
        }

        foreach ($rules as $key => $rule) {
            if ($rule->isObjectRule()) {
                $object = idx($objects, $rule->getTriggerObjectPHID());
                if (!$object) {
                    unset($rules[$key]);
                    continue;
                }
                $rule->attachTriggerObject($object);
            }
        }

        return $rules;
    }

    /**
     * @param AphrontDatabaseConnection $conn
     * @return array|void
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        $where = parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'rule.id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'rule.phid', $this->phids]);
        }

        if ($this->authorPHIDs !== null) {
            $this->andWhere(['IN', 'rule.author_phid', $this->authorPHIDs]);
        }

        if ($this->ruleTypes !== null) {
            $this->andWhere(['IN', 'rule.rule_type', $this->ruleTypes]);
        }

        if ($this->contentTypes !== null) {
            $this->andWhere(['IN', 'rule.content_type', $this->contentTypes]);
        }

        if ($this->disabled !== null) {
            $this->andWhere([
                'rule.is_disabled' => (int)$this->disabled
            ]);
        }

        if ($this->active !== null) {
            $this->andWhere([
                'rule.is_disabled' => (int)(!$this->active)
            ]);
        }

        if ($this->datasourceQuery !== null) {
            $this->andWhere(['LIKE', 'rule.name', $this->datasourceQuery, false]);
        }

        if ($this->triggerObjectPHIDs !== null) {
            $this->andWhere(['IN', 'rule.trigger_object_phid', $this->triggerObjectPHIDs]);

        }

        if ($this->affectedObjectPHIDs !== null) {
            $this->andWhere(['IN', 'edge_affects.dst', $this->affectedObjectPHIDs]);
        }

        return $where;
    }

    /**
     * @return array|void
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildJoinClauseParts()
    {
        parent::buildJoinClauseParts();

        if ($this->affectedObjectPHIDs !== null) {
            $this->innerJoin($this->newResultObject()->edgeBaseTableName() . "_" . PhabricatorEdgeConfig::TABLE_NAME_EDGE . " edge_affects", "rule.phid = edge_affects.src AND edge_affects.type = :type", [
                ':type' => HeraldRuleActionAffectsObjectEdgeType::EDGECONST
            ]);
        }
    }

    /**
     * @param array $rules
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    private function validateRuleAuthors(array $rules)
    {
        // "Global" and "Object" rules always have valid authors.
        foreach ($rules as $key => $rule) {
            if ($rule->isGlobalRule() || $rule->isObjectRule()) {
                $rule->attachValidAuthor(true);
                unset($rules[$key]);
                continue;
            }
        }

        if (!$rules) {
            return;
        }

        // For personal rules, the author needs to exist and not be disabled.
        /** @var array $user_phids */
        $user_phids = mpull($rules, 'getAuthorPHID');
        $users = PhabricatorUser::find()
            ->setViewer($this->getViewer())
            ->withPHIDs($user_phids)
            ->execute();

        /** @var PhabricatorUser[] $users */
        $users = mpull($users, null, 'getPHID');

        foreach ($rules as $key => $rule) {
            $author_phid = $rule->getAuthorPHID();
            if (empty($users[$author_phid])) {
                $rule->attachValidAuthor(false);
                continue;
            }
            if (!$users[$author_phid]->isUserActivated()) {
                $rule->attachValidAuthor(false);
                continue;
            }

            $rule->attachValidAuthor(true);
            $rule->attachAuthor($users[$author_phid]);
        }
    }


    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'rule';
    }

    /**
     * If this query belongs to an application, return the application class name
     * here. This will prevent the query from returning results if the viewer can
     * not access the application.
     *
     * If this query does not belong to an application, return `null`.
     *
     * @return string|null Application class name.
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorHeraldApplication::className();
    }
}
