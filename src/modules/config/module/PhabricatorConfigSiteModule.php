<?php

namespace orangins\modules\config\module;

use orangins\aphront\site\AphrontSite;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use PhutilInvalidStateException;
use Yii;

/**
 * Class PhabricatorConfigSiteModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
final class PhabricatorConfigSiteModule extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'site';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return Yii::t("app", 'Sites');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed|AphrontTableView
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $sites = AphrontSite::getAllSites();

        $rows = array();
        foreach ($sites as $key => $site) {
            $rows[] = array(
                $site->getPriority(),
                $key,
                $site->getDescription(),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    Yii::t("app", 'Priority'),
                    Yii::t("app", 'Class'),
                    Yii::t("app", 'Description'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'pri',
                    'wide',
                ));
    }

}
