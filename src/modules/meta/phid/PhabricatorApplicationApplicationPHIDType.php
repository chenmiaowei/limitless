<?php

namespace orangins\modules\meta\phid;


use orangins\modules\meta\application\PhabricatorApplicationsApplication;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorApplicationApplicationPHIDType
 * @package orangins\modules\meta\phid
 * @author 陈妙威
 */
final class PhabricatorApplicationApplicationPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'APPS';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t('app', 'Application');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTypeIcon()
    {
        return 'fa-globe';
    }

    /**
     * @return \orangins\lib\db\ActiveRecord|null
     * @author 陈妙威
     */
    public function newObject()
    {
        return null;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorApplicationsApplication::class;
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorApplicationQuery
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return (new PhabricatorApplicationQuery())
            ->withPHIDs($phids);
    }

    /**
     * @param PhabricatorHandleQuery $query
     * @param array $handles
     * @param array $objects
     * @author 陈妙威
     */
    public function loadHandles(PhabricatorHandleQuery $query,
                                   array $handles,
                                   array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $application = $objects[$phid];

            $handle
                ->setName($application->getName())
                ->setURI($application->getApplicationURI())
                ->setIcon($application->getIcon());
        }
    }

}
