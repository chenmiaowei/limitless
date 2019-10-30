<?php

namespace orangins\modules\auth\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\auth\sshkey\PhabricatorAuthSSHPublicKey;
use orangins\modules\auth\sshkey\PhabricatorSSHPublicKeyInterface;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorAuthSSHKeyQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     *
     */
    const AUTHSTRUCT_CACHEKEY = 'ssh.authstruct';

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
    private $objectPHIDs;
    /**
     * @var
     */
    private $keys;
    /**
     * @var
     */
    private $isActive;

    /**
     * @author 陈妙威
     * @throws Exception
     */
    public static function deleteSSHKeyCache()
    {
        $cache = PhabricatorCaches::getMutableCache();
        $authfile_key = self::AUTHSTRUCT_CACHEKEY;
        $cache->deleteKey($authfile_key);
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
     * @param array $object_phids
     * @return $this
     * @author 陈妙威
     */
    public function withObjectPHIDs(array $object_phids)
    {
        $this->objectPHIDs = $object_phids;
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function withKeys(array $keys)
    {
        assert_instances_of($keys, PhabricatorAuthSSHPublicKey::class);
        $this->keys = $keys;
        return $this;
    }

    /**
     * @param $active
     * @return $this
     * @author 陈妙威
     */
    public function withIsActive($active)
    {
        $this->isActive = $active;
        return $this;
    }

    /**
     * @return null|PhabricatorAuthSSHKey
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorAuthSSHKey();
    }

    /**
     * @return array|null|ActiveRecord[]
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage($this->newResultObject());
    }

    /**
     * @param array $keys
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function willFilterPage(array $keys)
    {
        $object_phids = mpull($keys, 'getObjectPHID');

        $objects = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($object_phids)
            ->execute();
        $objects = mpull($objects, null, 'getPHID');

        foreach ($keys as $key => $ssh_key) {
            $object = ArrayHelper::getValue($objects, $ssh_key->getObjectPHID());

            // We must have an object, and that object must be a valid object for
            // SSH keys.
            if (!$object || !($object instanceof PhabricatorSSHPublicKeyInterface)) {
                $this->didRejectResult($ssh_key);
                unset($keys[$key]);
                continue;
            }

            $ssh_key->attachObject($object);
        }

        return $keys;
    }

    /**
     * @return array|void
     * @throws PhabricatorInvalidQueryCursorException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
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

        if ($this->objectPHIDs !== null) {
            $this->andWhere(['IN', 'object_phid', $this->objectPHIDs]);
        }

        if ($this->keys !== null) {
            $sql = array();
            foreach ($this->keys as $key) {
                $sql[] = [
                    'key_type' => $key->getType(),
                    'key_index' => $key->getHash(),
                ];
            }

            if (count($sql) === 1) {
                $this->andWhere(head($sql));
            } else {
                $this->andWhere(ArrayHelper::merge(['OR'], $sql));
            }
        }

        if ($this->isActive !== null) {
            if ($this->isActive) {
                $this->andWhere(['is_active' => 1]);
            } else {
                $this->andWhere('is_active IS NULL');
            }
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
