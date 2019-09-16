<?php

namespace orangins\modules\conduit\query;

use orangins\modules\conduit\interfaces\PhabricatorConduitResultInterface;
use orangins\modules\search\engineextension\PhabricatorSearchEngineExtension;

/**
 * Class ConduitResultSearchEngineExtension
 * @package orangins\modules\conduit\query
 * @author 陈妙威
 */
final class ConduitResultSearchEngineExtension
    extends PhabricatorSearchEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'conduit';

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 1500;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return \Yii::t("app",'Support for ConduitResultInterface');
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return ($object instanceof PhabricatorConduitResultInterface);
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getFieldSpecificationsForConduit($object)
    {
        return $object->getFieldSpecificationsForConduit();
    }

    /**
     * @param $object
     * @param $data
     * @return array
     * @author 陈妙威
     */
    public function getFieldValuesForConduit($object, $data)
    {
        return $object->getFieldValuesForConduit();
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getSearchAttachments($object)
    {
        return $object->getConduitSearchAttachments();
    }

}
