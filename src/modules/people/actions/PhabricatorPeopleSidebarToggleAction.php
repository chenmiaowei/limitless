<?php

namespace orangins\modules\people\actions;

use orangins\lib\response\AphrontJSONResponse;
use orangins\lib\response\AphrontPureJSONResponse;
use orangins\modules\settings\editors\PhabricatorUserPreferencesEditor;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\setting\PhabricatorSidebarToggleSetting;

/**
 * Class PhabricatorPeopleDeleteAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleSidebarToggleAction
    extends PhabricatorPeopleAction
{
    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return false;
    }
    /**
     * @return AphrontJSONResponse
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $preferences = PhabricatorUserPreferences::loadUserPreferences($viewer);

        $editor = (new PhabricatorUserPreferencesEditor())
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true);

        $xactions = array();
        $xactions[] = $preferences->newTransaction(PhabricatorSidebarToggleSetting::SETTINGKEY, $request->getInt(PhabricatorSidebarToggleSetting::SETTINGKEY, 1));
        $editor->applyTransactions($preferences, $xactions);

        return (new AphrontJSONResponse())->setContent([]);
    }
}
