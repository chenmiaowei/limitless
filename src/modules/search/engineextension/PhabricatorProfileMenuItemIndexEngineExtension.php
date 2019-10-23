<?php

namespace orangins\modules\search\engineextension;

use orangins\modules\search\edge\PhabricatorProfileMenuItemAffectsObjectEdgeType;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;

/**
 * Class PhabricatorProfileMenuItemIndexEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorProfileMenuItemIndexEngineExtension
    extends PhabricatorEdgeIndexEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'profile.menu.item';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Profile Menu Item');
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldIndexObject($object)
    {
        if (!($object instanceof PhabricatorProfileMenuItemConfiguration)) {
            return false;
        }

        return true;
    }

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    protected function getIndexEdgeType()
    {
        return PhabricatorProfileMenuItemAffectsObjectEdgeType::EDGECONST;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $object
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    protected function getIndexDestinationPHIDs($object)
    {
        return $object->getAffectedObjectPHIDs();
    }
}
