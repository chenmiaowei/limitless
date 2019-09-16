<?php

namespace orangins\modules\config\module;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\config\check\PhabricatorSetupCheck;

/**
 * Class PhabricatorConfigSetupCheckModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
final class PhabricatorConfigSetupCheckModule
    extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'setup';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app",'Setup Checks');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     * @throws \ReflectionException
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $checks = PhabricatorSetupCheck::loadAllChecks();
        $rows = array();
        foreach ($checks as $key => $check) {
            if ($check->isPreflightCheck()) {
                $icon = (new PHUIIconView())->setIcon('fa-plane blue');
            } else {
                $icon = (new PHUIIconView())->setIcon('fa-times grey');
            }

            $rows[] = array(
                $check->getExecutionOrder(),
                $icon,
                $check->getClassShortName(),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app",'Order'),
                    \Yii::t("app",'Preflight'),
                    \Yii::t("app",'Class'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    null,
                    'pri wide',
                ));
    }

}
