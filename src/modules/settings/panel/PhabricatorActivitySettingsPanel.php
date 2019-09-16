<?php

namespace orangins\modules\settings\panel;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontCursorPagerView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\people\query\PhabricatorPeopleLogQuery;
use orangins\modules\people\view\PhabricatorUserLogView;
use orangins\modules\settings\panelgroup\PhabricatorSettingsLogsPanelGroup;

/**
 * Class PhabricatorActivitySettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorActivitySettingsPanel extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'activity';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Activity Logs');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsLogsPanelGroup::PANELGROUPKEY;
    }

    /**
     * @param AphrontRequest $request
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $request->getViewer();
        $user = $this->getUser();

        $pager = (new AphrontCursorPagerView())
            ->readFromRequest($request);

        $logs = PhabricatorUserLog::find()
            ->setViewer($viewer)
            ->withRelatedPHIDs(array($user->getPHID()))
            ->executeWithCursorPager($pager);

        $table = (new PhabricatorUserLogView())
            ->setUser($viewer)
            ->setLogs($logs);

        $panel = $this->newBox(\Yii::t("app",'Account Activity Logs'), $table);

        $pager_box = (new PHUIBoxView())
            ->addMargin(PHUI::MARGIN_LARGE)
            ->appendChild($pager);

        return array($panel, $pager_box);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isManagementPanel()
    {
        return true;
    }

}
