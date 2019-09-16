<?php

namespace orangins\modules\oauthserver\phid;

use orangins\modules\oauthserver\models\PhabricatorOAuthClientAuthorization;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\oauthserver\query\PhabricatorOAuthClientAuthorizationQuery;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorOAuthServerClientAuthorizationPHIDType
 * @package orangins\modules\oauthserver\phid
 * @author 陈妙威
 */
final class PhabricatorOAuthServerClientAuthorizationPHIDType
    extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'OASA';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return pht('OAuth Authorization');
    }

    /**
     * @return null|PhabricatorOAuthClientAuthorization
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorOAuthClientAuthorization();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorOAuthServerApplication::className();
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorOAuthClientAuthorizationQuery
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorOAuthClientAuthorization::find()
            ->withPHIDs($phids);
    }

    /**
     * @param PhabricatorHandleQuery $query
     * @param array $handles
     * @param array $objects
     * @author 陈妙威
     */
    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $authorization = $objects[$phid];
            $handle->setName(pht('Authorization %d', $authorization->getID()));
        }
    }

}
