<?php

namespace orangins\modules\settings\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\modules\settings\editors\PhabricatorUserPreferencesEditor;
use orangins\modules\settings\models\PhabricatorUserPreferences;

/**
 * Class PhabricatorSettingsAdjustAction
 * @package orangins\modules\settings\actions
 * @author 陈妙威
 */
final class PhabricatorSettingsAdjustAction
    extends PhabricatorAction
{

    /**
     * @return AphrontAjaxResponse
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
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

        $key = $request->getStr('key');
        $value = $request->getStr('value');

        $xactions = array();
        $xactions[] = $preferences->newTransaction($key, $value);

        $editor->applyTransactions($preferences, $xactions);

        return (new AphrontAjaxResponse())->setContent(array());
    }
}
