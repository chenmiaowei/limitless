<?php

namespace orangins\modules\oauthserver\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;

/**
 * Class PhabricatorOAuthServerClientQuery
 * @package orangins\modules\oauthserver\query
 * @author 陈妙威
 */
final class PhabricatorOAuthServerClientQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $creatorPHIDs;

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
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withCreatorPHIDs(array $phids)
    {
        $this->creatorPHIDs = $phids;
        return $this;
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     * @throws \Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $this->buildWhereClause();
        $this->buildOrderClause();
        $this->buildLimitClause();
        return $this->all();
    }

    /**
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        if ($this->ids) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->creatorPHIDs) {
            $this->andWhere(['IN', 'creator_phid', $this->creatorPHIDs]);
        }
        $this->buildPagingClause();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorOAuthServerApplication::className();
    }

}
