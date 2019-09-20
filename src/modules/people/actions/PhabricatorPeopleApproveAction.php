<?php

namespace orangins\modules\people\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorPeopleApproveAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleApproveAction
    extends PhabricatorPeopleAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView|Aphront404Response
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        /** @var PhabricatorUser $user */
        $user = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        $done_uri = $this->getApplicationURI('query/approval/');

        if ($user->getIsApproved()) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Already Approved'))
                ->appendChild(\Yii::t("app", 'This user has already been approved.'))
                ->addCancelButton($done_uri);
        }

        if ($request->isFormPost()) {
            (new PhabricatorUserEditor())
                ->setActor($viewer)
                ->approveUser($user, true);

            $title = \Yii::t("app",
                'Phabricator Account "%s" Approved',
                $user->getUsername());

            $body = sprintf(
                "%s\n\n  %s\n\n",
                \Yii::t("app",
                    'Your Phabricator account (%s) has been approved by %s. You can ' .
                    'login here:',
                    $user->getUsername(),
                    $viewer->getUsername()),
                PhabricatorEnv::getProductionURI('/'));

            $mail = (new PhabricatorMetaMTAMail())
                ->addTos(array($user->getPHID()))
                ->addCCs(array($viewer->getPHID()))
                ->setSubject('[Phabricator] ' . $title)
                ->setForceDelivery(true)
                ->setBody($body)
                ->saveAndSend();

            return (new AphrontRedirectResponse())->setURI($done_uri);
        }

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'Confirm Approval'))
            ->appendChild(
                \Yii::t("app",
                    'Allow %s to access this Phabricator install?',
                    phutil_tag('strong', array(), $user->getUsername())))
            ->addCancelButton($done_uri)
            ->addSubmitButton(\Yii::t("app", 'Approve Account'));
    }
}
