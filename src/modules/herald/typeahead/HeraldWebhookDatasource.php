<?php

namespace orangins\modules\herald\typeahead;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class HeraldWebhookDatasource
 * @package orangins\modules\herald\typeahead
 * @author 陈妙威
 */
final class HeraldWebhookDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return pht('Type a webhook name...');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return pht('Browse Webhooks');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return 'PhabricatorHeraldApplication';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function loadResults()
    {
        $viewer = $this->getViewer();
        $raw_query = $this->getRawQuery();

        $hooks = (new HeraldWebhookQuery())
            ->setViewer($viewer)
            ->execute();

        $handles = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(mpull($hooks, 'getPHID'))
            ->execute();

        $results = array();
        foreach ($hooks as $hook) {
            $handle = $handles[$hook->getPHID()];

            $result = (new PhabricatorTypeaheadResult())
                ->setName($handle->getFullName())
                ->setPHID($handle->getPHID());

            if ($hook->isDisabled()) {
                $result->setClosed(pht('Disabled'));
            }

            $results[] = $result;
        }

        return $results;
    }
}
