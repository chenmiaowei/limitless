<?php

namespace orangins\modules\herald\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\herald\application\PhabricatorHeraldApplication;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\db\ActiveRecord;

/**
 * This is the ActiveQuery class for [[\orangins\modules\herald\models\HeraldAction]].
 *
 * @see \orangins\modules\herald\models\HeraldActionRecord
 */
class HeraldActionQuery extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
    * @var array
    */
    private $id;

    /**
    * @param array $id
    * @return $this
    * @author 陈妙威
    */
    public function withId($id)
    {
        $this->id[] = $id;
        return $this;
    }
    /**
    * @param array $ids
    * @return $this
    * @author 陈妙威
    */
    public function withIds($ids)
    {
        $this->id = $ids;
        return $this;
    }

    /**
    * @var array
    */
    private $rule_id;

    /**
    * @param array $rule_id
    * @return $this
    * @author 陈妙威
    */
    public function withRule_id($rule_id)
    {
        $this->rule_id[] = $rule_id;
        return $this;
    }
    /**
    * @param array $rule_ids
    * @return $this
    * @author 陈妙威
    */
    public function withRule_ids($rule_ids)
    {
        $this->rule_id = $rule_ids;
        return $this;
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
        parent::buildWhereClauseParts();

        if ($this->id !== null) {
            $this->andWhere(['IN', 'id', $this->id]);
        }
        if ($this->rule_id !== null) {
            $this->andWhere(['IN', 'rule_id', $this->rule_id]);
        }

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
