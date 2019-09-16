<?php

namespace orangins\modules\people\engineextension;

use orangins\modules\people\typeahead\PhabricatorPeopleDatasource;
use orangins\modules\search\engineextension\PhabricatorDatasourceEngineExtension;

/**
 * Class PhabricatorPeopleDatasourceEngineExtension
 * @package orangins\modules\people\engineextension
 * @author 陈妙威
 */
final class PhabricatorPeopleDatasourceEngineExtension
    extends PhabricatorDatasourceEngineExtension
{

    /**
     * @return array
     * @author 陈妙威
     */
    public function newQuickSearchDatasources()
    {
        return array(
            new PhabricatorPeopleDatasource(),
        );
    }

    /**
     * @param $query
     * @return null|string
     * @author 陈妙威
     */
    public function newJumpURI($query)
    {
        $viewer = $this->getViewer();

        // Send "u" to the user directory.
        if (preg_match('/^u\z/i', $query)) {
            return '/people/';
        }

        // Send "u <string>" to the user's profile page.
        $matches = null;
        if (preg_match('/^u\s+(.+)\z/i', $query, $matches)) {
            $raw_query = $matches[1];

            // TODO: We could test that this is a valid username and jump to
            // a search in the user directory if it isn't.

            return urisprintf(
                '/p/%s/',
                $raw_query);
        }

        return null;
    }
}
