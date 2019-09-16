<?php

namespace orangins\modules\config\module;

use orangins\lib\request\AphrontRequest;

final class PhabricatorConfigSiteModule extends PhabricatorConfigModule
{

    public function getModuleKey()
    {
        return 'site';
    }

    public function getModuleName()
    {
        return \Yii::t("app",'Sites');
    }

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
                    \Yii::t("app",'Priority'),
                    \Yii::t("app",'Class'),
                    \Yii::t("app",'Description'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'pri',
                    'wide',
                ));
    }

}
