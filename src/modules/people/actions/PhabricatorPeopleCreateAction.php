<?php

namespace orangins\modules\people\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormRadioButtonControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\guides\guidance\PhabricatorGuidanceEngine;
use orangins\modules\people\capability\PeopleCreateUsersCapability;
use orangins\modules\people\guidance\PhabricatorPeopleCreateGuidanceContext;

/**
 * Class PhabricatorPeopleCreateAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleCreateAction
    extends PhabricatorPeopleAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $session = \Yii::$app->session->get("__id");

        $request = $this->getRequest();
        $admin = $request->getViewer();


        (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $admin,
            $request,
            $this->getApplicationURI());

        $v_type = 'standard';
        if ($request->isFormPost()) {
            $v_type = $request->getStr('type');

            if ($v_type == 'standard' || $v_type == 'bot' || $v_type == 'list') {
                return (new AphrontRedirectResponse())->setURI(
                    $this->getApplicationURI('index/new', ['type' => $v_type]));
            }
        }

        $title = \Yii::t("app", 'Create New User');

        $standard_caption = \Yii::t("app",
            'Create a standard user account. These users can log in to Phabricator, ' .
            'use the web interface and API, and receive email.');

        $standard_admin = \Yii::t("app",
            'Administrators are limited in their ability to access or edit these ' .
            'accounts after account creation.');

        $bot_caption = \Yii::t("app",
            'Create a bot/script user account, to automate interactions with other ' .
            'systems. These users can not use the web interface, but can use the ' .
            'API.');

        $bot_admin = \Yii::t("app",
            'Administrators have greater access to edit these accounts.');

        $types = array();

        $can_create = $this->hasApplicationCapability(
            PeopleCreateUsersCapability::CAPABILITY);
        if ($can_create) {
            $types[] = array(
                'type' => 'standard',
                'name' => \Yii::t("app", 'Create Standard User'),
                'help' => \Yii::t("app", 'Create a standard user account.'),
            );
        }

        $types[] = array(
            'type' => 'bot',
            'name' => \Yii::t("app", 'Create Bot User'),
            'help' => \Yii::t("app", 'Create a new user for use with automated scripts.'),
        );

        $types[] = array(
            'type' => 'list',
            'name' => \Yii::t("app", 'Create Mailing List User'),
            'help' => \Yii::t("app",
                'Create a mailing list user to represent an existing, external ' .
                'mailing list like a Google Group or a Mailman list.'),
        );

        $buttons = (new AphrontFormRadioButtonControl())
            ->setLabel(\Yii::t("app", 'Account Type'))
            ->setName('type')
            ->setValue($v_type);

        foreach ($types as $type) {
            $buttons->addButton($type['type'], $type['name'], $type['help']);
        }

        $form = (new AphrontFormView())
            ->setUser($admin)
            ->appendRemarkupInstructions(
                \Yii::t("app",
                    'Choose the type of user account to create. For a detailed ' .
                    'explanation of user account types, see [[ {0} | User Guide: ' .
                    'Account Roles ]].', [
                        PhabricatorEnv::getDoclink('User Guide: Account Roles')
                    ]))
            ->appendChild($buttons)
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($this->getApplicationURI())
                    ->setValue(\Yii::t("app", 'Continue')));

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);
        $crumbs->setBorder(true);

        $box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        $guidance_context = new PhabricatorPeopleCreateGuidanceContext();

        $guidance = (new PhabricatorGuidanceEngine())
            ->setViewer($admin)
            ->setGuidanceContext($guidance_context)
            ->newInfoView();

        $view = (new PHUITwoColumnView())
            ->setFooter(
                array(
                    $guidance,
                    $box,
                ));

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}
