<?php

namespace orangins\modules\auth\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\auth\application\PhabricatorAuthApplication;

/**
 * Class PhabricatorAuthTemporaryTokenQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthTemporaryTokenQuery extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $tokenResources;
    /**
     * @var
     */
    private $tokenTypes;
    /**
     * @var
     */
    private $userPHIDs;
    /**
     * @var
     */
    private $expired;
    /**
     * @var
     */
    private $tokenCodes;

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
     * @param array $resources
     * @return $this
     * @author 陈妙威
     */
    public function withTokenResources(array $resources)
    {
        $this->tokenResources = $resources;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withTokenTypes(array $types)
    {
        $this->tokenTypes = $types;
        return $this;
    }

    /**
     * @param $expired
     * @return $this
     * @author 陈妙威
     */
    public function withExpired($expired)
    {
        $this->expired = $expired;
        return $this;
    }

    /**
     * @param array $codes
     * @return $this
     * @author 陈妙威
     */
    public function withTokenCodes(array $codes)
    {
        $this->tokenCodes = $codes;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $phids)
    {
        $this->userPHIDs = $phids;
        return $this;
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @throws \AphrontAccessDeniedQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $records = $this->loadStandardPage();
        return $records;
    }

    /**
     * @return array|void
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->tokenResources !== null) {
            $this->andWhere(['IN', 'token_resource', $this->tokenResources]);
        }

        if ($this->tokenTypes !== null) {
            $this->andWhere(['IN', 'token_type', $this->tokenTypes]);
        }

        if ($this->expired !== null) {
            if ($this->expired) {
                $this->andWhere(['<=', 'token_expires', time()]);
            } else {
                $this->andWhere(['>', 'token_expires', time()]);
            }
        }

        if ($this->tokenCodes !== null) {
            $this->andWhere(['IN', 'token_code', $this->tokenCodes]);
        }

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->userPHIDs]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorAuthApplication::class;
    }

}
