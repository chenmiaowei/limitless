<?php

namespace orangins\modules\search\query;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorFulltextResultSet
 * @package orangins\modules\search\query
 * @author 陈妙威
 */
final class PhabricatorFulltextResultSet extends OranginsObject
{

    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $fulltextTokens;

    /**
     * @param $phids
     * @return $this
     * @author 陈妙威
     */
    public function setPHIDs($phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPHIDs()
    {
        return $this->phids;
    }

    /**
     * @param $fulltext_tokens
     * @return $this
     * @author 陈妙威
     */
    public function setFulltextTokens($fulltext_tokens)
    {
        $this->fulltextTokens = $fulltext_tokens;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFulltextTokens()
    {
        return $this->fulltextTokens;
    }

}
