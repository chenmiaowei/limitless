<?php
namespace orangins\modules\dashboard\actions\dashboard;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\editors\PhabricatorDashboardTransactionEditor;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardTransaction;
use orangins\modules\dashboard\xaction\dashboard\PhabricatorDashboardStatusTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorDashboardArchiveController
 * @author 陈妙威
 */
final class PhabricatorDashboardArchiveController
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

        $dashboard = PhabricatorDashboard::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$dashboard) {
            return new Aphront404Response();
        }

        $view_uri = $dashboard->getURI();

        if ($request->isFormPost()) {
            if ($dashboard->isArchived()) {
                $new_status = PhabricatorDashboard::STATUS_ACTIVE;
            } else {
                $new_status = PhabricatorDashboard::STATUS_ARCHIVED;
            }

            $xactions = array();

            $xactions[] = (new PhabricatorDashboardTransaction())
                ->setTransactionType(
                    PhabricatorDashboardStatusTransaction::TRANSACTIONTYPE)
                ->setNewValue($new_status);

            (new PhabricatorDashboardTransactionEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->applyTransactions($dashboard, $xactions);

            return (new AphrontRedirectResponse())->setURI($view_uri);
        }

        if ($dashboard->isArchived()) {
            $title = \Yii::t("app",'Activate Dashboard');
            $body = \Yii::t("app",'This dashboard will become active again.');
            $button = \Yii::t("app",'Activate Dashboard');
        } else {
            $title = \Yii::t("app",'Archive Dashboard');
            $body = \Yii::t("app",'This dashboard will be marked as archived.');
            $button = \Yii::t("app",'Archive Dashboard');
        }

        return $this->newDialog()
            ->setTitle($title)
            ->appendChild($body)
            ->addCancelButton($view_uri)
            ->addSubmitButton($button);
    }

}
