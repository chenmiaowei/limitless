<?php

namespace orangins\modules\people\actions;

use AphrontDuplicateKeyQueryException;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormStaticControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorPeopleRenameAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleRenameAction
    extends PhabricatorPeopleAction
{

    /**
     * @return Aphront404Response|AphrontDialogView
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
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

        $done_uri = $this->getApplicationURI("manage/{$id}/");

        (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $done_uri);

        $errors = array();

        $v_username = $user->getUsername();
        $e_username = true;
        if ($request->isFormPost()) {
            $v_username = $request->getStr('username');

            if (!strlen($v_username)) {
                $e_username = \Yii::t("app", 'Required');
                $errors[] = \Yii::t("app", 'New username is required.');
            } else if ($v_username == $user->getUsername()) {
                $e_username = \Yii::t("app", 'Invalid');
                $errors[] = \Yii::t("app", 'New username must be different from old username.');
            } else if (!PhabricatorUser::validateUsername($v_username)) {
                $e_username = \Yii::t("app", 'Invalid');
                $errors[] = PhabricatorUser::describeValidUsername();
            }

            if (!$errors) {
                try {
                    (new PhabricatorUserEditor())
                        ->setActor($viewer)
                        ->changeUsername($user, $v_username);

                    return (new AphrontRedirectResponse())->setURI($done_uri);
                } catch (AphrontDuplicateKeyQueryException $ex) {
                    $e_username = \Yii::t("app", 'Not Unique');
                    $errors[] = \Yii::t("app", 'Another user already has that username.');
                }
            }
        }

        $inst1 = \Yii::t("app",
            'Be careful when renaming users!');

        $inst2 = \Yii::t("app",
            'The old username will no longer be tied to the user, so anything ' .
            'which uses it (like old commit messages) will no longer associate ' .
            'correctly. (And, if you give a user a username which some other user ' .
            'used to have, username lookups will begin returning the wrong user.)');

        $inst3 = \Yii::t("app",
            'It is generally safe to rename newly created users (and test users ' .
            'and so on), but less safe to rename established users and unsafe to ' .
            'reissue a username.');

        $inst4 = \Yii::t("app",
            'Users who rely on password authentication will need to reset their ' .
            'password after their username is changed (their username is part of ' .
            'the salt in the password hash).');

        $inst5 = \Yii::t("app",
            'The user will receive an email notifying them that you changed their ' .
            'username, with instructions for logging in and resetting their ' .
            'password if necessary.');

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendChild(
                (new AphrontFormStaticControl())
                    ->setLabel(\Yii::t("app", 'Old Username'))
                    ->setValue($user->getUsername()))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'New Username'))
                    ->setValue($v_username)
                    ->setName('username')
                    ->setError($e_username));

        if ($errors) {
            $errors = (new PHUIInfoView())->setErrors($errors);
        }

        return $this->newDialog()
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->setTitle(\Yii::t("app", 'Change Username'))
            ->appendChild($errors)
            ->appendParagraph($inst1)
            ->appendParagraph($inst2)
            ->appendParagraph($inst3)
            ->appendParagraph($inst4)
            ->appendParagraph($inst5)
            ->appendParagraph(null)
            ->appendForm($form)
            ->addSubmitButton(\Yii::t("app", 'Rename User'))
            ->addCancelButton($done_uri);
    }

}
