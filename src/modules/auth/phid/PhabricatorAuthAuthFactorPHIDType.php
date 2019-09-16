<?php

namespace orangins\modules\auth\phid;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthFactorConfig;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorAuthAuthFactorPHIDType
 * @package orangins\modules\auth\phid
 * @author 陈妙威
 */
final class PhabricatorAuthAuthFactorPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'AFTR';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Auth Factor');
    }

    /**
     * @return null|PhabricatorAuthFactorConfig
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorAuthFactorConfig();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorAuthApplication::class;
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|void
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        // TODO: Maybe we need this eventually?
        throw new PhutilMethodNotImplementedException();
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
            $factor = $objects[$phid];

            $handle->setName($factor->getFactorName());
        }
    }

}
