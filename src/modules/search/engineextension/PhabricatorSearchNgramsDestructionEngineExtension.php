<?php

namespace orangins\modules\search\engineextension;

use orangins\modules\search\interfaces\PhabricatorNgramsInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\engine\PhabricatorDestructionEngineExtension;

/**
 * Class PhabricatorSearchNgramsDestructionEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorSearchNgramsDestructionEngineExtension
    extends PhabricatorDestructionEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'search.ngrams';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Search Ngram');
    }

    /**
     * @param PhabricatorDestructionEngine $engine
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canDestroyObject(
        PhabricatorDestructionEngine $engine,
        $object)
    {
        return ($object instanceof PhabricatorNgramsInterface);
    }

    /**
     * @param PhabricatorDestructionEngine $engine
     * @param PhabricatorNgramsInterface $object
     * @return mixed|void
     * @author 陈妙威
     */
    public function destroyObject(
        PhabricatorDestructionEngine $engine,
        $object)
    {

        foreach ($object->newNgrams() as $ngram) {
            queryfx(
                $ngram->establishConnection('w'),
                'DELETE FROM %T WHERE objectID = %d',
                $ngram->getTableName(),
                $object->getID());
        }
    }

}
