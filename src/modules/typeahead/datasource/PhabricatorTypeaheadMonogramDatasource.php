<?php

namespace orangins\modules\typeahead\datasource;

/**
 * Class PhabricatorTypeaheadMonogramDatasource
 * @package orangins\modules\typeahead\datasource
 * @author 陈妙威
 */
final class PhabricatorTypeaheadMonogramDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isBrowsable()
    {
        // This source isn't meaningfully browsable. Although it's technically
        // possible to let users browse through every object on an install, there
        // is no use case for it and it doesn't seem worth building.
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app", 'Browse Objects');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app", 'Type an object name...');
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return null;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function loadResults()
    {
        $viewer = $this->getViewer();
        $raw_query = $this->getRawQuery();

        $results = array();

        $objects = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withNames(array($raw_query))
            ->execute();
        if ($objects) {
            $handles = (new PhabricatorHandleQuery())
                ->setViewer($viewer)
                ->withPHIDs(mpull($objects, 'getPHID'))
                ->execute();
            $handle = head($handles);
            if ($handle) {
                $results[] = (new PhabricatorTypeaheadResult())
                    ->setName($handle->getFullName())
                    ->setDisplayType($handle->getTypeName())
                    ->setURI($handle->getURI())
                    ->setPHID($handle->getPHID())
                    ->setPriorityType('jump');
            }
        }

        return $results;
    }

}
