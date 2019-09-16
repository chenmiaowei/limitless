<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\OranginsObject;
use orangins\lib\helpers\OranginsUtil;
use PhutilClassMapQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;

/**
 * Class PhabricatorDatasourceEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
abstract class PhabricatorDatasourceEngineExtension extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;

    /**
     * @param PhabricatorUser $viewer
     * @return self
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function newQuickSearchDatasources()
    {
        return array();
    }

    /**
     * @param $query
     * @return null
     * @author 陈妙威
     */
    public function newJumpURI($query)
    {
        return null;
    }

    /**
     * @param PhabricatorTypeaheadCompositeDatasource $datasource
     * @return array
     * @author 陈妙威
     */
    public function newDatasourcesForCompositeDatasource(
        PhabricatorTypeaheadCompositeDatasource $datasource)
    {
        return array();
    }

    /**
     * @return PhabricatorDatasourceEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorDatasourceEngineExtension::class)
            ->execute();
    }

    /**
     * @return PhabricatorDatasourceEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllQuickSearchDatasources()
    {
        $extensions = self::getAllExtensions();

        $datasources = array();
        foreach ($extensions as $extension) {
            $datasources[] = $extension->newQuickSearchDatasources();
        }

        return array_mergev($datasources);
    }
}
