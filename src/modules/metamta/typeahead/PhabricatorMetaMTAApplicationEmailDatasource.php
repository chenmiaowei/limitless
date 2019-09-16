<?php

namespace orangins\modules\metamta\typeahead;

use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class PhabricatorMetaMTAApplicationEmailDatasource
 * @package orangins\modules\metamta\typeahead
 * @author 陈妙威
 */
final class PhabricatorMetaMTAApplicationEmailDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isBrowsable()
    {
        // TODO: Make this browsable.
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Email Addresses');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type an application email address...');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorMetaMTAApplication::class;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function loadResults()
    {
        $viewer = $this->getViewer();
        $raw_query = $this->getRawQuery();

        $emails = PhabricatorMetaMTAApplicationEmail::find()
            ->setViewer($viewer)
            ->withAddressPrefix($raw_query)
            ->setLimit($this->getLimit())
            ->execute();

        if ($emails) {
            $handles = (new PhabricatorHandleQuery())
                ->setViewer($viewer)
                ->withPHIDs(mpull($emails, 'getPHID'))
                ->execute();
        } else {
            $handles = array();
        }

        $results = array();
        foreach ($handles as $handle) {
            $results[] = (new PhabricatorTypeaheadResult())
                ->setName($handle->getName())
                ->setPHID($handle->getPHID());
        }

        return $results;
    }

}
