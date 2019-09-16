<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\search\ferret\PhabricatorFerretInterface;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\query\PhabricatorFulltextToken;
use PhutilSearchQueryCompiler;

/**
 * Class PhabricatorFerretSearchEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorFerretSearchEngineExtension
    extends PhabricatorSearchEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'ferret';

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
        return pht('Fulltext Search');
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
        return ($object instanceof PhabricatorFerretInterface);
    }

    /**
     * @param PhabricatorFerretInterface $object
     * @param PhabricatorCursorPagedPolicyAwareQuery $query
     * @param PhabricatorSavedQuery $saved
     * @param array $map
     * @throws \Exception
     * @author 陈妙威
     */
    public function applyConstraintsToQuery(
        $object,
        $query,
        PhabricatorSavedQuery $saved,
        array $map)
    {

        if (!strlen($map['query'])) {
            return;
        }

        $engine = $object->newFerretEngine();

        $raw_query = $map['query'];

        $compiler = (new PhutilSearchQueryCompiler())
            ->setEnableFunctions(true);

        $raw_tokens = $compiler->newTokens($raw_query);

        $fulltext_tokens = array();
        foreach ($raw_tokens as $raw_token) {
            $fulltext_token = (new PhabricatorFulltextToken())
                ->setToken($raw_token);

            $fulltext_tokens[] = $fulltext_token;
        }

        $query->withFerretConstraint($engine, $fulltext_tokens);
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getSearchFields($object)
    {
        $fields = array();

        $fields[] = (new PhabricatorSearchTextField())
            ->setKey('query')
            ->setLabel(pht('Query'))
            ->setDescription(
                pht(
                    'Find objects matching a fulltext search query. See ' .
                    '"Search User Guide" in the documentation for details.'));

        return $fields;
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getSearchAttachments($object)
    {
        return array();
    }


}
