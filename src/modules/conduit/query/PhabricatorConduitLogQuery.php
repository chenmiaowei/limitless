<?php

namespace orangins\modules\conduit\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\modules\conduit\application\PhabricatorConduitApplication;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\models\PhabricatorConduitMethodCallLog;

/**
 * This is the ActiveQuery class for [[ConduitMethodcalllog]].
 *
 * @see PhabricatorConduitMethodCallLog
 */
final class PhabricatorConduitLogQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $callerPHIDs;
    /**
     * @var
     */
    private $methods;
    /**
     * @var
     */
    private $methodStatuses;

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withCallerPHIDs(array $phids)
    {
        $this->callerPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $methods
     * @return $this
     * @author 陈妙威
     */
    public function withMethods(array $methods)
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @param array $statuses
     * @return $this
     * @author 陈妙威
     */
    public function withMethodStatuses(array $statuses)
    {
        $this->methodStatuses = $statuses;
        return $this;
    }

    /**
     * @return null|PhabricatorConduitMethodCallLog
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorConduitMethodCallLog();
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
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->callerPHIDs !== null) {
            $this->andWhere(['IN', 'caller_phid', $this->callerPHIDs]);
        }

        if ($this->methods !== null) {
            $this->andWhere(['IN', 'method', $this->methods]);
        }

        if ($this->methodStatuses !== null) {
            $statuses = array_fuse($this->methodStatuses);

            /** @var ConduitAPIMethod[] $methods */
            $methods = (new PhabricatorConduitMethodQuery())
                ->setViewer($this->getViewer())
                ->execute();

            $method_names = array();
            foreach ($methods as $method) {
                $status = $method->getMethodStatus();
                if (isset($statuses[$status])) {
                    $method_names[] = $method->getAPIMethodName();
                }
            }

            if (!$method_names) {
                throw new PhabricatorEmptyQueryException();
            }
            $this->andWhere(['IN', 'method', $method_names]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorConduitApplication::className();
    }

}
