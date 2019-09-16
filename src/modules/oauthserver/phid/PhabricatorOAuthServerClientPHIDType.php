<?php

namespace orangins\modules\oauthserver\phid;

use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerClientQuery;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorOAuthServerClientPHIDType
 * @package orangins\modules\oauthserver\phid
 * @author 陈妙威
 */
final class PhabricatorOAuthServerClientPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'OASC';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return pht('OAuth Application');
    }

    /**
     * @return null|PhabricatorOAuthServerClient
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorOAuthServerClient();
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
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorOAuthServerClientQuery
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorOAuthServerClient::find()
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
            $client = $objects[$phid];

            $handle->setName($client->getName());
        }
    }

}
