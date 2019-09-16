<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/16
 * Time: 11:47 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\conduit\phid;

use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;;
use orangins\modules\conduit\application\PhabricatorConduitApplication;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\query\PhabricatorConduitMethodQuery;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

class ConduitPHIDType extends PhabricatorPHIDType
{
    const TYPECONST = "COND";
    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return "接口";
    }

    /**
     * Get the class name for the application this type belongs to.
     *
     * @return string|null Class name of the corresponding application, or null
     *   if the type is not bound to an application.
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorConduitApplication::className();
    }

    /**
     * Build a @{class:PhabricatorPolicyAwareQuery} to load objects of this type
     * by PHID.
     *
     * If you can not build a single query which satisfies this requirement, you
     * can provide a dummy implementation for this method and overload
     * @{method:loadObjects} instead.
     *
     * @param PhabricatorObjectQuery $query Query being executed.
     * @param array<phid> PHIDs to load.
     * @return PhabricatorPolicyAwareQuery Query object which loads the
     *   specified PHIDs when executed.
     */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query,
                                            array $phids)
    {
        return  new PhabricatorConduitMethodQuery();
    }

    /**
     * Populate provided handles with application-specific data, like titles and
     * URIs.
     *
     * NOTE: The `$handles` and `$objects` lists are guaranteed to be nonempty
     * and have the same keys: subclasses are expected to load information only
     * for handles with visible objects.
     *
     * Because of this guarantee, a safe implementation will typically look like*
     *
     *   foreach ($handles as $phid => $handle) {
     *     $object = $objects[$phid];
     *
     *     $handle->setStuff($object->getStuff());
     *     // ...
     *   }
     *
     * In general, an implementation should call `setName()` and `setURI()` on
     * each handle at a minimum. See @{class:PhabricatorObjectHandle} for other
     * handle properties.
     *
     * @param PhabricatorHandleQuery $query Issuing query object.
     * @param array<PhabricatorObjectHandle>   Handles to populate with data.
     * @param array<Object>                    Objects for these PHIDs loaded by
     *                                        @{method:buildQueryForObjects()}.
     * @return void
     */
    public function loadHandles(PhabricatorHandleQuery $query,
                                array $handles,
                                array $objects)
    {
        foreach ($handles as $phid => $handle) {
            /** @var ConduitAPIMethod $user */
            $user = $objects[$phid];
            $handle
                ->setName($user->getAPIMethodName())
                ->setFullName($user->getMethodDescription());
        }
    }
}