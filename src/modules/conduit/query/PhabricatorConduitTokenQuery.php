<?php

namespace orangins\modules\conduit\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\conduit\application\PhabricatorConduitApplication;
use orangins\modules\conduit\models\PhabricatorConduitToken;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[ConduitToken]].
 *
 * @see PhabricatorConduitToken
 */
final class PhabricatorConduitTokenQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $objectPHIDs;
    /**
     * @var
     */
    private $expired;
    /**
     * @var
     */
    private $tokens;
    /**
     * @var
     */
    private $tokenTypes;
    /**
     * @var array
     */
    private $phids;

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
    public function withObjectPHIDs(array $phids)
    {
        $this->objectPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $tokens
     * @return $this
     * @author 陈妙威
     */
    public function withTokens(array $tokens)
    {
        $this->tokens = $tokens;
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
     * @return null|PhabricatorConduitToken
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorConduitToken();
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @author 陈妙威
     * @throws \Exception
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

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->objectPHIDs !== null) {
            $this->andWhere(['IN', 'object_phid', $this->objectPHIDs]);
        }

        if ($this->tokens !== null) {
            $this->andWhere(['IN', 'token', $this->tokens]);
        }

        if ($this->tokenTypes !== null) {
            $this->andWhere(['IN', 'token_type', $this->tokenTypes]);
        }

        if ($this->expired !== null) {
            if ($this->expired) {
                $this->andWhere("expires<=:expires", [
                    ":expires" => PhabricatorTime::getNow()
                ]);
            } else {
                $this->andWhere("expires IS NULL OR expires>:expires", [
                    ":expires" => PhabricatorTime::getNow()
                ]);
            }
        }
    }

    /**
     * @param array $tokens
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function willFilterPage(array $tokens)
    {
        $object_phids = mpull($tokens, 'getObjectPHID');
        $objects = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($object_phids)
            ->execute();
        $objects = mpull($objects, null, 'getPHID');

        foreach ($tokens as $key => $token) {
            $object = ArrayHelper::getValue($objects, $token->getObjectPHID(), null);
            if (!$object) {
                $this->didRejectResult($token);
                unset($tokens[$key]);
                continue;
            }
            $token->attachObject($object);
        }

        return $tokens;
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
