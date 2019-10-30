<?php

namespace orangins\modules\auth\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorAuthPasswordQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $objectPHIDs;
    /**
     * @var
     */
    private $passwordTypes;
    /**
     * @var
     */
    private $isRevoked;

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
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withPasswordTypes(array $types)
    {
        $this->passwordTypes = $types;
        return $this;
    }

    /**
     * @param $is_revoked
     * @return $this
     * @author 陈妙威
     */
    public function withIsRevoked($is_revoked)
    {
        $this->isRevoked = $is_revoked;
        return $this;
    }

    /**
     * @return PhabricatorAuthPassword|null
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorAuthPassword();
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
        return $this->loadStandardPage();
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

        if ($this->passwordTypes !== null) {
            $this->andWhere(['IN', 'password_type', $this->passwordTypes]);
        }

        if ($this->isRevoked !== null) {
            $this->andWhere([
                'is_revoked' => (int)$this->isRevoked
            ]);
        }
    }

    /**
     * @param array $passwords
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function willFilterPage(array $passwords)
    {
        $object_phids = OranginsUtil::mpull($passwords, 'getObjectPHID');

        $objects = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($object_phids)
            ->execute();
        $objects = OranginsUtil::mpull($objects, null, 'getPHID');

        foreach ($passwords as $key => $password) {
            $object = ArrayHelper::getValue($objects, $password->getObjectPHID());
            if (!$object) {
                unset($passwords[$key]);
                $this->didRejectResult($password);
                continue;
            }

            $password->attachObject($object);
        }

        return $passwords;
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
