<?php

namespace orangins\modules\search\engineextension;

use orangins\modules\search\field\PhabricatorIDsSearchField;
use orangins\modules\search\field\PhabricatorPHIDsSearchField;
use orangins\modules\search\models\PhabricatorSavedQuery;

/**
 * Class PhabricatorIDsSearchEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorIDsSearchEngineExtension
    extends PhabricatorSearchEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'ids';

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
        return pht('Supports ID/PHID Queries');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 1000;
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return true;
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getSearchFields($object)
    {
        return array(
            (new PhabricatorIDsSearchField())
                ->setKey('ids')
                ->setLabel(pht('IDs'))
                ->setDescription(
                    pht('Search for objects with specific IDs.')),
            (new PhabricatorPHIDsSearchField())
                ->setKey('phids')
                ->setLabel(pht('PHIDs'))
                ->setDescription(
                    pht('Search for objects with specific PHIDs.')),
        );
    }

    /**
     * @param $object
     * @param $query
     * @param PhabricatorSavedQuery $saved
     * @param array $map
     * @author 陈妙威
     */
    public function applyConstraintsToQuery(
        $object,
        $query,
        PhabricatorSavedQuery $saved,
        array $map)
    {

        if ($map['ids']) {
            $query->withIDs($map['ids']);
        }

        if ($map['phids']) {
            $query->withPHIDs($map['phids']);
        }

    }

}
