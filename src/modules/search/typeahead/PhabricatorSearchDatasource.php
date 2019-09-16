<?php

namespace orangins\modules\search\typeahead;

use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\engine\PhabricatorDatasourceEngine;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;

/**
 * Class PhabricatorSearchDatasource
 * @package orangins\modules\search\phid
 * @author 陈妙威
 */
final class PhabricatorSearchDatasource
    extends PhabricatorTypeaheadCompositeDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Results');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type an object name...');
    }

    /**
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorSearchApplication::className();
    }

    /**
     * @return \orangins\modules\search\engineextension\PhabricatorDatasourceEngineExtension[]|\orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource[]
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        $sources = (new PhabricatorDatasourceEngine())
            ->getAllQuickSearchDatasources();

        // These results are always rendered in the full browse display mode, so
        // set the browse flag on all component sources.
        foreach ($sources as $source) {
            $source->setIsBrowse(true);
        }

        return $sources;
    }
}
