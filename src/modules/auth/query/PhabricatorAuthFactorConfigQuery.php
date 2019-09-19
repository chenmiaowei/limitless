<?php

namespace orangins\modules\auth\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\auth\models\PhabricatorAuthFactorConfig;

/**
 * Class PhabricatorAuthFactorConfigQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthFactorConfigQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $userPHIDs;
    /**
     * @var
     */
    private $factorProviderPHIDs;
    /**
     * @var
     */
    private $factorProviderStatuses;

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
     * @param array $user_phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $user_phids)
    {
        $this->userPHIDs = $user_phids;
        return $this;
    }

    /**
     * @param array $provider_phids
     * @return $this
     * @author 陈妙威
     */
    public function withFactorProviderPHIDs(array $provider_phids)
    {
        $this->factorProviderPHIDs = $provider_phids;
        return $this;
    }

    /**
     * @param array $statuses
     * @return $this
     * @author 陈妙威
     */
    public function withFactorProviderStatuses(array $statuses)
    {
        $this->factorProviderStatuses = $statuses;
        return $this;
    }

    /**
     * @return PhabricatorAuthFactorConfig
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorAuthFactorConfig();
    }

    /**
     * @return array|mixed
     * @throws \AphrontAccessDeniedQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage($this->newResultObject());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'config.id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'config.phid', $this->phids]);
        }

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'config.user_phid', $this->userPHIDs]);
        }

        if ($this->factorProviderPHIDs !== null) {
            $this->andWhere(['IN', 'config.factor_provider_phid', $this->factorProviderPHIDs]);
        }

        if ($this->factorProviderStatuses !== null) {
            $this->andWhere(['IN', 'provider.status', $this->factorProviderStatuses]);
        }
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildJoinClauseParts()
    {
//        if ($this->factorProviderStatuses !== null) {
//            $joins[] = qsprintf(
//                $conn,
//                'JOIN %R provider ON config.factorProviderPHID = provider.phid',
//                new PhabricatorAuthFactorProvider());
//        }
    }

    /**
     * @param array $configs
     * @return array
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function willFilterPage(array $configs)
    {
//        $provider_phids = mpull($configs, 'getFactorProviderPHID');

//        $providers = id(new PhabricatorAuthFactorProviderQuery())
//            ->setViewer($this->getViewer())
//            ->withPHIDs($provider_phids)
//            ->execute();
//        $providers = mpull($providers, null, 'getPHID');
//
//        foreach ($configs as $key => $config) {
//            $provider = idx($providers, $config->getFactorProviderPHID());
//
//            if (!$provider) {
//                unset($configs[$key]);
//                $this->didRejectResult($config);
//                continue;
//            }
//
//            $config->attachFactorProvider($provider);
//        }

        return $configs;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'config';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return 'PhabricatorAuthApplication';
    }

}
