<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\search\index\PhabricatorIndexEngine;
use orangins\modules\search\index\PhabricatorIndexEngineExtension;
use orangins\modules\search\interfaces\PhabricatorNgramsInterface;

/**
 * Class PhabricatorNgramsIndexEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorNgramsIndexEngineExtension
    extends PhabricatorIndexEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'ngrams';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Ngrams Engine');
    }

    /**
     * @param PhabricatorNgramsInterface $object
     * @return string|null
     * @author 陈妙威
     */
    public function getIndexVersion($object)
    {
        $ngrams = $object->newNgrams();
        $map = mpull($ngrams, 'getValue', 'getNgramKey');
        ksort($map);
        $serialized = serialize($map);

        return PhabricatorHash::digestForIndex($serialized);
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldIndexObject($object)
    {
        return ($object instanceof PhabricatorNgramsInterface);
    }

    /**
     * @param PhabricatorIndexEngine $engine
     * @param PhabricatorNgramsInterface $object
     * @return mixed|void
     * @author 陈妙威
     */
    public function indexObject(
        PhabricatorIndexEngine $engine,
        $object)
    {

        foreach ($object->newNgrams() as $ngram) {
            $ngram->writeNgram($object->getID());
        }
    }
}
