<?php

namespace orangins\modules\search\query;

use Exception;
use orangins\lib\infrastructure\edges\constants\PhabricatorEdgeConfig;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\edge\PhabricatorProfileMenuItemAffectsObjectEdgeType;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorProfileMenuItemConfigurationQuery
 * @package orangins\modules\search\query
 * @author 陈妙威
 */
final class PhabricatorProfileMenuItemConfigurationQuery
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
    private $profilePHIDs;
    /**
     * @var
     */
    private $customPHIDs;
    /**
     * @var
     */
    private $includeGlobal;
    /**
     * @var
     */
    private $affectedObjectPHIDs;

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
    public function withProfilePHIDs(array $phids)
    {
        $this->profilePHIDs = $phids;
        return $this;
    }

    /**
     * @param array $phids
     * @param bool $include_global
     * @return $this
     * @author 陈妙威
     */
    public function withCustomPHIDs(array $phids, $include_global = false)
    {
        $this->customPHIDs = $phids;
        $this->includeGlobal = $include_global;
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
     * @return null|PhabricatorProfileMenuItemConfiguration
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorProfileMenuItemConfiguration();
    }

    /**
     * @return array|null|ActiveRecord[]
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage($this->newResultObject());
    }

    /**
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
            $where[] = qsprintf(
                $conn,
                'config.id IN (%Ld)',
                $this->ids);
        }

        if ($this->phids !== null) {
            $where[] = qsprintf(
                $conn,
                'config.phid IN (%Ls)',
                $this->phids);
        }

        if ($this->profilePHIDs !== null) {
            $where[] = qsprintf(
                $conn,
                'config.profilePHID IN (%Ls)',
                $this->profilePHIDs);
        }

        if ($this->customPHIDs !== null) {
            if ($this->customPHIDs && $this->includeGlobal) {
                $where[] = qsprintf(
                    $conn,
                    'config.customPHID IN (%Ls) OR config.customPHID IS NULL',
                    $this->customPHIDs);
            } else if ($this->customPHIDs) {
                $where[] = qsprintf(
                    $conn,
                    'config.customPHID IN (%Ls)',
                    $this->customPHIDs);
            } else {
                $where[] = qsprintf(
                    $conn,
                    'config.customPHID IS NULL');
            }
        }

        if ($this->affectedObjectPHIDs !== null) {
            $where[] = qsprintf(
                $conn,
                'affected.dst IN (%Ls)',
                $this->affectedObjectPHIDs);
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
        $joins = parent::buildJoinClauseParts();

        if ($this->affectedObjectPHIDs !== null) {
            $joins[] = qsprintf(
                $conn,
                'JOIN %T affected ON affected.src = config.phid
          AND affected.type = %d',
                PhabricatorEdgeConfig::TABLE_NAME_EDGE,
                PhabricatorProfileMenuItemAffectsObjectEdgeType::EDGECONST);
        }

        return $joins;
    }

    /**
     * @param array $page
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function willFilterPage(array $page)
    {
        $items = PhabricatorProfileMenuItem::getAllMenuItems();
        foreach ($page as $key => $item) {
            $item_type = ArrayHelper::getValue($items, $item->getMenuItemKey());
            if (!$item_type) {
                $this->didRejectResult($item);
                unset($page[$key]);
                continue;
            }
            $item_type = clone $item_type;
            $item_type->setViewer($this->getViewer());
            $item->attachMenuItem($item_type);
        }

        if (!$page) {
            return array();
        }

        $profile_phids = mpull($page, 'getProfilePHID');

        $profiles = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($profile_phids)
            ->execute();
        $profiles = mpull($profiles, null, 'getPHID');

        foreach ($page as $key => $item) {
            $profile = ArrayHelper::getValue($profiles, $item->getProfilePHID());
            if (!$profile) {
                $this->didRejectResult($item);
                unset($page[$key]);
                continue;
            }

            $item->attachProfileObject($profile);
        }

        return $page;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSearchApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'config';
    }

}
