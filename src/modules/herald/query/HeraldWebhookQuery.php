<?php

namespace orangins\modules\herald\query;

use AphrontAccessDeniedQueryException;
use Exception;
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
 * This is the ActiveQuery class for [[\orangins\modules\herald\models\HeraldWebhook]].
 *
 * @see \orangins\modules\herald\models\HeraldWebhook
 */
class HeraldWebhookQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $status;

    /**
    * @param array $status
    * @return $this
    * @author 陈妙威
    */
    public function withStatus($status)
    {
        $this->status[] = $status;
        return $this;
    }
    /**
    * @param array $statuss
    * @return $this
    * @author 陈妙威
    */
    public function withStatuses($statuss)
    {
        $this->status = $statuss;
        return $this;
    }

    /**
    * @var array
    */
    private $phid;

    /**
    * @param array $phid
    * @return $this
    * @author 陈妙威
    */
    public function withPHID($phid)
    {
        $this->phid[] = $phid;
        return $this;
    }
    /**
    * @param array $phids
    * @return $this
    * @author 陈妙威
    */
    public function withPhids($phids)
    {
        $this->phid = $phids;
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
    * @throws Exception
    * @author 陈妙威
    */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->id !== null) {
            $this->andWhere(['IN', 'id', $this->id]);
        }
        if ($this->status !== null) {
            $this->andWhere(['IN', 'status', $this->status]);
        }
        if ($this->phid !== null) {
            $this->andWhere(['IN', 'phid', $this->phid]);
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
