<?php

namespace orangins\modules\search\engine;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\engineextension\PhabricatorDatasourceEngineExtension;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;

/**
 * Class PhabricatorDatasourceEngine
 * @package orangins\modules\search\engine
 * @author 陈妙威
 */
final class PhabricatorDatasourceEngine extends OranginsObject
{
    /**
     * @var
     */
    private $viewer;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @return PhabricatorDatasourceEngineExtension[]
     * @author 陈妙威
     */
    public function getAllQuickSearchDatasources()
    {
        return PhabricatorDatasourceEngineExtension::getAllQuickSearchDatasources();
    }

    /**
     * @param $query
     * @return null
     * @author 陈妙威
     */
    public function newJumpURI($query)
    {
        $viewer = $this->getViewer();
        $extensions = PhabricatorDatasourceEngineExtension::getAllExtensions();
        foreach ($extensions as $extension) {
            $phabricatorDatasourceEngineExtension = clone $extension;
            $jump_uri = $phabricatorDatasourceEngineExtension
                ->setViewer($viewer)
                ->newJumpURI($query);

            if ($jump_uri !== null) {
                return $jump_uri;
            }
        }

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
        $viewer = $this->getViewer();
        $extensions = PhabricatorDatasourceEngineExtension::getAllExtensions();

        $sources = array();
        foreach ($extensions as $extension) {
            $phabricatorDatasourceEngineExtension = clone $extension;
            $extension_sources = $phabricatorDatasourceEngineExtension
                ->setViewer($viewer)
                ->newDatasourcesForCompositeDatasource($datasource);
            foreach ($extension_sources as $extension_source) {
                $sources[] = $extension_source;
            }
        }
        return $sources;
    }

}
