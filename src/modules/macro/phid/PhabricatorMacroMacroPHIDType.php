<?php

namespace orangins\modules\macro\phid;

use orangins\modules\macro\models\PhabricatorFileImageMacro;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorMacroMacroPHIDType
 * @package orangins\modules\macro\phid
 * @author 陈妙威
 */
final class PhabricatorMacroMacroPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'MCRO';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return pht('Image Macro');
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return 'PhabricatorMacroApplication';
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getTypeIcon()
    {
        return 'fa-meh-o';
    }

    /**
     * @return PhabricatorFileImageMacro|null
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorFileImageMacro();
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\orangins\modules\macro\models\FileImagemacroQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorFileImageMacro::find()
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
            $macro = $objects[$phid];

            $id = $macro->getID();
            $name = $macro->getName();

            $handle->setName($name);
            $handle->setFullName(pht('Image Macro "%s"', $name));
            $handle->setURI("/macro/view/{$id}/");
        }
    }

}
