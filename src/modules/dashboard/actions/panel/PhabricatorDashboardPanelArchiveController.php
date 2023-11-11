<?php

namespace orangins\modules\dashboard\actions\panel;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\editors\PhabricatorDashboardPanelTransactionEditor;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\models\PhabricatorDashboardPanelTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardPanelStatusTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorDashboardPanelArchiveController
 * @package orangins\modules\dashboard\actions\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelArchiveController
    extends PhabricatorDashboardController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
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
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $panel = PhabricatorDashboardPanel::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$panel) {
            return new Aphront404Response();
        }

        $next_uri = '/' . $panel->getMonogram();

        if ($request->isFormPost()) {
            $xactions = array();
            $xactions[] = (new PhabricatorDashboardPanelTransaction())
                ->setTransactionType(
                    PhabricatorDashboardPanelStatusTransaction::TRANSACTIONTYPE)
                ->setNewValue((int)!$panel->getIsArchived());

            (new PhabricatorDashboardPanelTransactionEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->applyTransactions($panel, $xactions);

            return (new AphrontRedirectResponse())->setURI($next_uri);
        }

        if ($panel->getIsArchived()) {
            $title = \Yii::t("app",'Activate Panel?');
            $body = \Yii::t("app",
                'This panel will be reactivated and appear in other interfaces as ' .
                'an active panel.');
            $submit_text = \Yii::t("app",'Activate Panel');
        } else {
            $title = \Yii::t("app",'Archive Panel?');
            $body = \Yii::t("app",
                'This panel will be archived and no longer appear in lists of active ' .
                'panels.');
            $submit_text = \Yii::t("app",'Archive Panel');
        }

        return $this->newDialog()
            ->setTitle($title)
            ->appendParagraph($body)
            ->addSubmitButton($submit_text)
            ->addCancelButton($next_uri);
    }

}
