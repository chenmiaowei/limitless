<?php

namespace orangins\modules\people\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\models\PhabricatorUserLog;

/**
 * Class PhabricatorPeopleLogQuery
 * @package orangins\modules\people\query
 * @see PhabricatorUserLog
 * @author 陈妙威
 */
final class PhabricatorPeopleLogQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $actorPHIDs;
    /**
     * @var
     */
    private $userPHIDs;
    /**
     * @var
     */
    private $relatedPHIDs;
    /**
     * @var
     */
    private $sessionKeys;
    /**
     * @var
     */
    private $actions;
    /**
     * @var
     */
    private $remoteAddressPrefix;
    /**
     * @var
     */
    private $dateCreatedMin;
    /**
     * @var
     */
    private $dateCreatedMax;

    /**
     * @param array $actor_phids
     * @return $this
     * @author 陈妙威
     */
    public function withActorPHIDs(array $actor_phids)
    {
        $this->actorPHIDs = $actor_phids;
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
     * @param array $related_phids
     * @return $this
     * @author 陈妙威
     */
    public function withRelatedPHIDs(array $related_phids)
    {
        $this->relatedPHIDs = $related_phids;
        return $this;
    }

    /**
     * @param array $session_keys
     * @return $this
     * @author 陈妙威
     */
    public function withSessionKeys(array $session_keys)
    {
        $this->sessionKeys = $session_keys;
        return $this;
    }

    /**
     * @param array $actions
     * @return $this
     * @author 陈妙威
     */
    public function withActions(array $actions)
    {
        $this->actions = $actions;
        return $this;
    }

    /**
     * @param $remote_address_prefix
     * @return $this
     * @author 陈妙威
     */
    public function withRemoteAddressPrefix($remote_address_prefix)
    {
        $this->remoteAddressPrefix = $remote_address_prefix;
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedBetween($min, $max)
    {
        $this->dateCreatedMin = $min;
        $this->dateCreatedMax = $max;
        return $this;
    }

    /**
     * @return null|PhabricatorUserLog
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorUserLog();
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
       parent::buildWhereClauseParts();

        if ($this->actorPHIDs !== null) {
            $this->andWhere(['IN', 'actor_phid', $this->actorPHIDs]);
        }

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->userPHIDs]);
        }

        if ($this->relatedPHIDs !== null) {
            $this->andWhere([
                "OR",
                ['IN', 'actor_phid', $this->relatedPHIDs],
                ['IN', 'user_phid', $this->relatedPHIDs],
            ]);
        }

        if ($this->sessionKeys !== null) {
            $this->andWhere(['IN', 'session', $this->sessionKeys]);
        }

        if ($this->actions !== null) {
            $this->andWhere(['IN', 'action', $this->actions]);
        }

        if ($this->remoteAddressPrefix !== null) {
            $this->andWhere(['LIKE', 'remote_addr', "%" . $this->remoteAddressPrefix]);
        }

        if ($this->dateCreatedMin !== null) {
            $this->andWhere(['>=', 'created_at', $this->dateCreatedMin]);
        }

        if ($this->dateCreatedMax !== null) {
            $this->andWhere(['<=', 'created_at', $this->dateCreatedMax]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }

}
