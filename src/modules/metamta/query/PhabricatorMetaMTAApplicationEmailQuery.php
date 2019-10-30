<?php

namespace orangins\modules\metamta\query;

use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorMetaMTAApplicationEmailQuery
 * @package orangins\modules\metamta\query
 * @author 陈妙威
 */
final class PhabricatorMetaMTAApplicationEmailQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $addresses;
    /**
     * @var
     */
    private $addressPrefix;
    /**
     * @var
     */
    private $applicationPHIDs;

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
     * @param array $addresses
     * @return $this
     * @author 陈妙威
     */
    public function withAddresses(array $addresses)
    {
        $this->addresses = $addresses;
        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     * @author 陈妙威
     */
    public function withAddressPrefix($prefix)
    {
        $this->addressPrefix = $prefix;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withApplicationPHIDs(array $phids)
    {
        $this->applicationPHIDs = $phids;
        return $this;
    }

    /**
     * @return array|null|ActiveRecord[]
     * @throws \Exception
     *@author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @param array $app_emails
     * @return array
     * @throws ReflectionException
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function willFilterPage(array $app_emails)
    {
        /** @var PhabricatorMetaMTAApplicationEmail[] $app_emails_map */
        $app_emails_map = OranginsUtil::mgroup($app_emails, 'getApplicationPHID');
        $applications = (new PhabricatorApplicationQuery())
            ->setViewer($this->getViewer())
            ->withPHIDs(array_keys($app_emails_map))
            ->execute();
        $applications = OranginsUtil::mpull($applications, null, 'getPHID');

        foreach ($app_emails_map as $app_phid => $app_emails_group) {
            foreach ($app_emails_group as $app_email) {
                $application = ArrayHelper::getValue($applications, $app_phid);
                if (!$application) {
                    unset($app_emails[$app_phid]);
                    continue;
                }
                $app_email->attachApplication($application);
            }
        }
        return $app_emails;
    }

    /**
     * @return array|void
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
      parent::buildWhereClauseParts();

        if ($this->addresses !== null) {
            $this->andWhere(['IN', 'address', $this->addresses]);
        }

        if ($this->addressPrefix !== null) {
            $this->andWhere(['LIKE', 'address',  $this->addressPrefix . "%"]);
        }

        if ($this->applicationPHIDs !== null) {
            $this->andWhere(['IN', 'application_phid', $this->applicationPHIDs]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'appemail';
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorMetaMTAApplication::class;
    }

}
