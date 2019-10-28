<?php

namespace orangins\modules\herald\typeahead;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

final class HeraldRuleDatasource
    extends PhabricatorTypeaheadDatasource
{

    public function getPlaceholderText()
    {
        return pht('Type a Herald rule name...');
    }

    public function getBrowseTitle()
    {
        return pht('Browse Herald Rules');
    }

    public function getDatasourceApplicationClass()
    {
        return 'PhabricatorHeraldApplication';
    }

    public function loadResults()
    {
        $viewer = $this->getViewer();
        $raw_query = $this->getRawQuery();

        $query = HeraldRule::find()
            ->setViewer($viewer);

        if (preg_match('/^[hH]\d+\z/', $raw_query)) {
            $id = trim($raw_query, 'hH');
            $id = (int)$id;
            $query->withIDs(array($id));
        } else {
            $query->withDatasourceQuery($raw_query);
        }

        $rules = $query->execute();

        $handles = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(mpull($rules, 'getPHID'))
            ->execute();

        $results = array();
        foreach ($rules as $rule) {
            $handle = $handles[$rule->getPHID()];

            $result = (new PhabricatorTypeaheadResult())
                ->setName($handle->getFullName())
                ->setPHID($handle->getPHID());

            if ($rule->getIsDisabled()) {
                $result->setClosed(pht('Archived'));
            }

            $results[] = $result;
        }

        return $results;
    }
}
