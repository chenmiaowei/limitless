<?php

namespace orangins\modules\typeahead\datasource;

use Exception;

/**
 * Class PhabricatorTypeaheadRuntimeCompositeDatasource
 * @package orangins\modules\typeahead\datasource
 * @author 陈妙威
 */
final class PhabricatorTypeaheadRuntimeCompositeDatasource extends PhabricatorTypeaheadCompositeDatasource
{

    /**
     * @var array
     */
    private $datasources = array();

    /**
     * @return array
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        return $this->datasources;
    }

    /**
     * @throws Exception
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        throw new Exception(\Yii::t("app",'This source is not usable directly.'));
    }

    /**
     * @param PhabricatorTypeaheadDatasource $source
     * @return $this
     * @author 陈妙威
     */
    public function addDatasource(PhabricatorTypeaheadDatasource $source)
    {
        $this->datasources[] = $source;
        return $this;
    }

}
