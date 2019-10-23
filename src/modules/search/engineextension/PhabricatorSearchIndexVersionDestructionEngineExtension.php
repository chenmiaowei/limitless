<?php

namespace orangins\modules\search\engineextension;

use orangins\modules\search\models\PhabricatorSearchIndexVersion;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\engine\PhabricatorDestructionEngineExtension;

final class PhabricatorSearchIndexVersionDestructionEngineExtension
    extends PhabricatorDestructionEngineExtension
{

    const EXTENSIONKEY = 'search.index.version';

    public function getExtensionName()
    {
        return pht('Search Index Versions');
    }

    public function destroyObject(
        PhabricatorDestructionEngine $engine,
        $object)
    {

        $table = new PhabricatorSearchIndexVersion();

        queryfx(
            $table->establishConnection('w'),
            'DELETE FROM %T WHERE objectPHID = %s',
            $table->getTableName(),
            $object->getPHID());
    }

}
