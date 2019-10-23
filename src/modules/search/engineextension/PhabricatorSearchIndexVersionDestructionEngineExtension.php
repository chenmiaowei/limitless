<?php

namespace orangins\modules\search\engineextension;

use orangins\modules\search\models\PhabricatorSearchIndexVersion;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\engine\PhabricatorDestructionEngineExtension;

/**
 * Class PhabricatorSearchIndexVersionDestructionEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorSearchIndexVersionDestructionEngineExtension
    extends PhabricatorDestructionEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'search.index.version';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Search Index Versions');
    }

    /**
     * @param PhabricatorDestructionEngine $engine
     * @param $object
     * @return mixed|void
     * @author 陈妙威
     */
    public function destroyObject(
        PhabricatorDestructionEngine $engine,
        $object)
    {
        PhabricatorSearchIndexVersion::deleteAll([
            'object_phid' => $object->getPHID()
        ]);
    }
}
