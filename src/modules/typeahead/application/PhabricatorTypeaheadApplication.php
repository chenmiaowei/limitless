<?php

namespace orangins\modules\typeahead\application;


use orangins\lib\PhabricatorApplication;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadMonogramDatasource;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadProxyDatasource;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadRuntimeCompositeDatasource;
use Yii;

/**
 * Class PhabricatorTypeaheadApplication
 * @author 陈妙威
 */
final class PhabricatorTypeaheadApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'typeahead';
    }
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\typeahead\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/typeahead/index/query';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return Yii::t('app', 'Typeahead');
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLaunchable()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall()
    {
        return false;
    }
}
