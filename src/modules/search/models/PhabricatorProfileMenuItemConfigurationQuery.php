<?php

namespace orangins\modules\search\models;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[SearchProfilepanelconfiguration]].
 *
 * @see PhabricatorProfileMenuItemConfiguration
 */
class PhabricatorProfileMenuItemConfigurationQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withAffectedObjectPHIDs(array $phids) {
        $this->affectedObjectPHIDs = $phids;
        return $this;
    }

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
     * @return PhabricatorProfileMenuItemConfiguration
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorProfileMenuItemConfiguration();
    }

    /**
     * @return mixed
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $records = $this->loadStandardPage();
        return $records;
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

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->profilePHIDs !== null) {
            $this->andWhere(['IN', 'profile_phid', $this->profilePHIDs]);
        }

        if ($this->customPHIDs !== null) {
            if ($this->customPHIDs && $this->includeGlobal) {
                $this->andWhere([
                    'OR',
                    ['IN', 'custom_phid', $this->customPHIDs],
                    'custom_phid IS NULL'
                ]);
            } else if ($this->customPHIDs) {
                $this->andWhere(['IN', 'custom_phid', $this->customPHIDs]);
            } else {
               $this->andWhere('custom_phid IS NULL');
            }
        }
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
            $item_type = idx($items, $item->getMenuItemKey());
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
     * @return string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSearchApplication::class;
    }
}
