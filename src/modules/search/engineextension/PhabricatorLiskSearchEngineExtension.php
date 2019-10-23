<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\conduit\interfaces\PhabricatorConduitSearchFieldSpecification;

/**
 * Class PhabricatorLiskSearchEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorLiskSearchEngineExtension
    extends PhabricatorSearchEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'lisk';

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Lisk Builtin Properties');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 5000;
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        if (!($object instanceof ActiveRecordPHID)) {
            return false;
        }

//    if (!$object->getConfigOption(LiskDAO::CONFIG_TIMESTAMPS)) {
//      return false;
//    }

        return true;
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getFieldSpecificationsForConduit($object)
    {
        return array(
            (new PhabricatorConduitSearchFieldSpecification())
                ->setKey('dateCreated')
                ->setType('int')
                ->setDescription(
                    pht('Epoch timestamp when the object was created.')),
            (new PhabricatorConduitSearchFieldSpecification())
                ->setKey('dateModified')
                ->setType('int')
                ->setDescription(
                    pht('Epoch timestamp when the object was last updated.')),
        );
    }

    /**
     * @param $object
     * @param $data
     * @return array
     * @author 陈妙威
     */
    public function getFieldValuesForConduit($object, $data)
    {
        return array(
            'dateCreated' => (int)$object->created_at,
            'dateModified' => (int)$object->updated_at,
        );
    }

}
