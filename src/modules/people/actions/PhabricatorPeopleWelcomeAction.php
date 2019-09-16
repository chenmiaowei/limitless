<?php

namespace orangins\modules\people\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\people\models\PhabricatorUser;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleWelcomeController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleWelcomeAction
    extends PhabricatorPeopleAction
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $admin = $this->getViewer();

        $user = PhabricatorUser::find()
            ->setViewer($admin)
            ->withIDs(array($request->getURIData('id')))
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        $profile_uri = Url::to(['/people/index/manage', 'id' => $user->id]);

        if (!$user->canEstablishWebSessions()) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app",'Not a Normal User'))
                ->appendParagraph(
                    \Yii::t("app",
                        'You can not send this user a welcome mail because they are not ' .
                        'a normal user and can not log in to the web interface. Special ' .
                        'users (like bots and mailing lists) are unable to establish web ' .
                        'sessions.'))
                ->addCancelButton($profile_uri, \Yii::t("app",'Done'));
        }

        if ($request->isFormPost()) {
            $user->sendWelcomeEmail($admin);
            return (new AphrontRedirectResponse())->setURI($profile_uri);
        }

        return $this->newDialog()
            ->setTitle(\Yii::t("app",'Send Welcome Email'))
            ->appendParagraph(
                \Yii::t("app",
                    'This will send the user another copy of the "Welcome to ' .
                    '{0}" email that users normally receive when their ' .
                    'accounts are created.', PhabricatorEnv::getEnvConfig("orangins.site-name")))
            ->appendParagraph(
                \Yii::t("app",
                    'The email contains a link to log in to their account. Sending ' .
                    'another copy of the email can be useful if the original was lost ' .
                    'or never sent.'))
            ->appendParagraph(\Yii::t("app",'The email will identify you as the sender.'))
            ->addSubmitButton(\Yii::t("app",'Send Email'))
            ->addCancelButton($profile_uri);
    }

}
