<?php

namespace orangins\modules\people\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorPeopleEmpowerAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleEmpowerAction
    extends PhabricatorPeopleAction
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $id = $request->getURIData('id');

        $user = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        $done_uri = $this->getApplicationURI("index/manage", ['id' => $id]);

        (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $done_uri);

        if ($user->getPHID() == $viewer->getPHID()) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Your Way is Blocked'))
                ->appendParagraph(
                    \Yii::t("app",
                        'After a time, your efforts fail. You can not adjust your own ' .
                        'status as an administrator.'))
                ->addCancelButton($done_uri, \Yii::t("app", 'Accept Fate'));
        }

        if ($request->isFormPost()) {
            (new PhabricatorUserEditor())
                ->setActor($viewer)
                ->makeAdminUser($user, !$user->getIsAdmin());

            return (new AphrontRedirectResponse())->setURI($done_uri);
        }

        if ($user->getIsAdmin()) {
            $title = \Yii::t("app", 'Remove as Administrator?');
            $short = \Yii::t("app", 'Remove Administrator');
            $body = \Yii::t("app",
                'Remove %s as an administrator? They will no longer be able to ' .
                'perform administrative functions on this Phabricator install.',
                phutil_tag('strong', array(), $user->getUsername()));
            $submit = \Yii::t("app", 'Remove Administrator');
        } else {
            $title = \Yii::t("app", 'Make Administrator?');
            $short = \Yii::t("app", 'Make Administrator');
            $body = \Yii::t("app",
                'Empower %s as an administrator? They will be able to create users, ' .
                'approve users, make and remove administrators, delete accounts, and ' .
                'perform other administrative functions on this Phabricator install.',
                phutil_tag('strong', array(), $user->getUsername()));
            $submit = \Yii::t("app", 'Make Administrator');
        }

        return $this->newDialog()
            ->setTitle($title)
            ->setShortTitle($short)
            ->appendParagraph($body)
            ->addCancelButton($done_uri)
            ->addSubmitButton($submit);
    }

}
