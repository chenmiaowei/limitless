<?php

namespace orangins\modules\policy\engineextension;

use orangins\modules\conduit\interfaces\PhabricatorConduitSearchFieldSpecification;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\search\engineextension\PhabricatorSearchEngineExtension;

/**PhabricatorPolicyQuery
 * Class PhabricatorPolicySearchEngineExtension
 * @package orangins\modules\policy\engineextension
 * @author 陈妙威
 */
final class PhabricatorPolicySearchEngineExtension
    extends PhabricatorSearchEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'policy';

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
        return pht('Support for Policies');
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return ($object instanceof PhabricatorPolicyInterface);
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 6000;
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
                ->setKey('policy')
                ->setType('map<string, wild>')
                ->setDescription(pht('Map of capabilities to current policies.')),
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
        $capabilities = $object->getCapabilities();

        $map = array();
        foreach ($capabilities as $capability) {
            $map[$capability] = $object->getPolicy($capability);
        }

        return array(
            'policy' => $map,
        );
    }
}
