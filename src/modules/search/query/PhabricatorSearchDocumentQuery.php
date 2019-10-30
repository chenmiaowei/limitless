<?php

namespace orangins\modules\search\query;

use Exception;
use orangins\lib\infrastructure\cluster\search\PhabricatorSearchService;
use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\models\PhabricatorSavedQuery;
use PhutilInvalidStateException;

/**
 * Class PhabricatorSearchDocumentQuery
 * @package orangins\modules\search\query
 * @author 陈妙威
 */
final class PhabricatorSearchDocumentQuery
    extends PhabricatorPolicyAwareQuery
{

    /**
     * @var PhabricatorSavedQuery
     */
    private $savedQuery;
    /**
     * @var
     */
    private $objectCapabilities;
    /**
     * @var
     */
    private $unfilteredOffset;
    /**
     * @var PhabricatorFulltextResultSet
     */
    private $fulltextResultSet;

    /**
     * @param PhabricatorSavedQuery $query
     * @return $this
     * @author 陈妙威
     */
    public function withSavedQuery(PhabricatorSavedQuery $query)
    {
        $this->savedQuery = $query;
        return $this;
    }

    /**
     * @param array $capabilities
     * @return $this
     * @author 陈妙威
     */
    public function requireObjectCapabilities(array $capabilities)
    {
        $this->objectCapabilities = $capabilities;
        return $this;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getRequiredObjectCapabilities()
    {
        if ($this->objectCapabilities) {
            return $this->objectCapabilities;
        }

        return $this->getRequiredCapabilities();
    }

    /**
     * @return PhabricatorFulltextResultSet
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function getFulltextResultSet()
    {
        if (!$this->fulltextResultSet) {
            throw new PhutilInvalidStateException('execute');
        }

        return $this->fulltextResultSet;
    }

    /**
     * @author 陈妙威
     */
    protected function willExecute()
    {
        $this->unfilteredOffset = 0;
        $this->fulltextResultSet = null;
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws Exception
     */
    protected function loadPage()
    {
        // NOTE: The offset and limit information in the inherited properties of
        // this object represent a policy-filtered offset and limit, but the
        // underlying query engine needs an unfiltered offset and limit. We keep
        // track of an unfiltered result offset internally.

        $savedQuery = clone($this->savedQuery);
        $query = $savedQuery
            ->setParameter('offset', $this->unfilteredOffset)
            ->setParameter('limit', $this->getRawResultLimit());

        $result_set = PhabricatorSearchService::newResultSet($query, $this);
        $phids = $result_set->getPHIDs();

        $this->fulltextResultSet = $result_set;
        $this->unfilteredOffset += count($phids);

        $handles = (new PhabricatorHandleQuery())
            ->setViewer($this->getViewer())
            ->requireObjectCapabilities($this->getRequiredObjectCapabilities())
            ->withPHIDs($phids)
            ->execute();

        // Retain engine order.
        $handles = array_select_keys($handles, $phids);

        return $handles;
    }

    /**
     * @param array $handles
     * @return array
     * @author 陈妙威
     */
    protected function willFilterPage(array $handles)
    {
        // NOTE: This is used by the object selector dialog to exclude the object
        // you're looking at, so that, e.g., a task can't be set as a dependency
        // of itself in the UI.

        // TODO: Remove this after object selection moves to ApplicationSearch.

        $exclude = array();
        if ($this->savedQuery) {
            $exclude_phids = $this->savedQuery->getParameter('excludePHIDs', array());
            $exclude = array_fuse($exclude_phids);
        }

        foreach ($handles as $key => $handle) {
            if (!$handle->isComplete()) {
                unset($handles[$key]);
                continue;
            }
            if ($handle->getPolicyFiltered()) {
                unset($handles[$key]);
                continue;
            }
            if (isset($exclude[$handle->getPHID()])) {
                unset($handles[$key]);
                continue;
            }
        }

        return $handles;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSearchApplication::className();
    }

    /**
     * @param array $page
     * @return $this|void
     * @author 陈妙威
     */
    protected function nextPage(array $page)
    {
        // We already updated the internal offset in `loadPage()` after loading
        // results, so we do not need to make any additional state updates here.
        return $this;
    }

}
