<?php

namespace orangins\modules\auth\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorAuthSessionQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthSessionQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $identityPHIDs;
    /**
     * @var
     */
    private $sessionKeys;
    /**
     * @var
     */
    private $sessionTypes;

    /**
     * @param array $identity_phids
     * @return $this
     * @author 陈妙威
     */
    public function withIdentityPHIDs(array $identity_phids)
    {
        $this->identityPHIDs = $identity_phids;
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function withSessionKeys(array $keys)
    {
        $this->sessionKeys = $keys;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withSessionTypes(array $types)
    {
        $this->sessionTypes = $types;
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
     * @return null
     * @author 陈妙威
     */
    protected function loadPage()
    {
       return $this->loadStandardPage();
    }

    /**
     * @param array $sessions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function willFilterPage(array $sessions)
    {
        $identity_phids = mpull($sessions, 'getUserPHID');

        $identity_objects = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($identity_phids)
            ->execute();
        $identity_objects = mpull($identity_objects, null, 'getPHID');

        foreach ($sessions as $key => $session) {
            $identity_object = ArrayHelper::getValue($identity_objects, $session->getUserPHID());
            if (!$identity_object) {
                unset($sessions[$key]);
            } else {
                $session->attachIdentityObject($identity_object);
            }
        }

        return $sessions;
    }

    /**
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function buildWhereClause()
    {
        $where = array();
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->identityPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->identityPHIDs]);
        }

        if ($this->sessionKeys !== null) {
            $hashes = array();
            foreach ($this->sessionKeys as $session_key) {
                $hashes[] = PhabricatorHash::weakDigest($session_key);
            }
            $this->andWhere(['IN', 'session_key', $hashes]);
        }

        if ($this->sessionTypes !== null) {
            $this->andWhere(['IN', 'type', $this->sessionTypes]);
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
