<?php

namespace orangins\modules\settings\panel;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormRadioButtonControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthFactorConfig;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\settings\panelgroup\PhabricatorSettingsAuthenticationPanelGroup;

/**
 * Class PhabricatorMultiFactorSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorMultiFactorSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'multifactor';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Multi-Factor Auth');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsAuthenticationPanelGroup::PANELGROUPKEY;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed|Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        if ($request->getExists('new')) {
            return $this->processNew($request);
        }

        if ($request->getExists('edit')) {
            return $this->processEdit($request);
        }

        if ($request->getExists('delete')) {
            return $this->processDelete($request);
        }

        $user = $this->getUser();
        $viewer = $request->getViewer();

        $factors = PhabricatorAuthFactorConfig::find()->andWhere(['user_phid' => $user->getPHID()])->all();

        $rows = array();
        $rowc = array();

        $highlight_id = $request->getInt('id');
        foreach ($factors as $factor) {

            $impl = $factor->getImplementation();
            if ($impl) {
                $type = $impl->getFactorName();
            } else {
                $type = $factor->getFactorKey();
            }

            if ($factor->getID() == $highlight_id) {
                $rowc[] = 'highlighted';
            } else {
                $rowc[] = null;
            }

            $rows[] = array(
                javelin_tag(
                    'a',
                    array(
                        'href' => $this->getPanelURI('?edit=' . $factor->getID()),
                        'sigil' => 'workflow',
                    ),
                    $factor->getFactorName()),
                $type,
                OranginsViewUtil::phabricator_datetime($factor->created_at, $viewer),
                javelin_tag(
                    'a',
                    array(
                        'href' => $this->getPanelURI('?delete=' . $factor->getID()),
                        'sigil' => 'workflow',
                        'class' => 'small button button-grey',
                    ),
                    \Yii::t("app",'Remove')),
            );
        }

        $table = new AphrontTableView($rows);
        $table->setNoDataString(
            \Yii::t("app","You haven't added any authentication factors to your account yet."));
        $table->setHeaders(
            array(
                \Yii::t("app",'Name'),
                \Yii::t("app",'Type'),
                \Yii::t("app",'Created'),
                '',
            ));
        $table->setColumnClasses(
            array(
                'wide pri',
                '',
                'right',
                'action',
            ));
        $table->setRowClasses($rowc);
        $table->setDeviceVisibility(
            array(
                true,
                false,
                false,
                true,
            ));

        $help_uri = PhabricatorEnv::getDoclink(
            'User Guide: Multi-Factor Authentication');

        $buttons = array();

        $buttons[] = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-plus')
            ->setText(\Yii::t("app",'Add Auth Factor'))
            ->setHref($this->getPanelURI('?new=true'))
            ->setWorkflow(true)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));

        $buttons[] = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-book')
            ->setText(\Yii::t("app",'Help'))
            ->setHref($help_uri)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));

        return $this->newBox(\Yii::t("app",'Authentication Factors'), $table, $buttons);
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function processNew(AphrontRequest $request)
    {
        $viewer = $request->getViewer();
        $user = $this->getUser();

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $this->getPanelURI());

        $factors = PhabricatorAuthFactor::getAllFactors();

        $form = (new AphrontFormView())
            ->setUser($viewer);

        $type = $request->getStr('type');
        if (empty($factors[$type]) || !$request->isFormPost()) {
            $factor = null;
        } else {
            $factor = $factors[$type];
        }

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->addHiddenInput('new', true);

        if ($factor === null) {
            $choice_control = (new AphrontFormRadioButtonControl())
                ->setName('type')
                ->setValue(key($factors));

            foreach ($factors as $available_factor) {
                $choice_control->addButton(
                    $available_factor->getFactorKey(),
                    $available_factor->getFactorName(),
                    $available_factor->getFactorDescription());
            }

            $dialog->appendParagraph(
                \Yii::t("app",
                    'Adding an additional authentication factor improves the security ' .
                    'of your account. Choose the type of factor to add:'));

            $form
                ->appendChild($choice_control);

        } else {
            $dialog->addHiddenInput('type', $type);

            $config = $factor->processAddFactorForm(
                $form,
                $request,
                $user);

            if ($config) {
                $config->save();

                $log = PhabricatorUserLog::initializeNewLog(
                    $viewer,
                    $user->getPHID(),
                    PhabricatorUserLog::ACTION_MULTI_ADD);
                $log->save();

                $user->updateMultiFactorEnrollment();

                // Terminate other sessions so they must log in and survive the
                // multi-factor auth check.

                (new PhabricatorAuthSessionEngine())->terminateLoginSessions(
                    $user,
                    $request->getCookie(PhabricatorCookies::COOKIE_SESSION));

                return (new AphrontRedirectResponse())
                    ->setURI($this->getPanelURI('?id=' . $config->getID()));
            }
        }

        $dialog
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->setTitle(\Yii::t("app",'Add Authentication Factor'))
            ->appendChild($form->buildLayoutView())
            ->addSubmitButton(\Yii::t("app",'Continue'))
            ->addCancelButton($this->getPanelURI());

        return (new AphrontDialogResponse())
            ->setDialog($dialog);
    }

    /**
     * @param AphrontRequest $request
     * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function processEdit(AphrontRequest $request)
    {
        $viewer = $request->getUser();
        $user = $this->getUser();

        $factor = (new PhabricatorAuthFactorConfig())->loadOneWhere(
            'id = %d AND userPHID = %s',
            $request->getInt('edit'),
            $user->getPHID());
        if (!$factor) {
            return new Aphront404Response();
        }

        $e_name = true;
        $errors = array();
        if ($request->isFormPost()) {
            $name = $request->getStr('name');
            if (!strlen($name)) {
                $e_name = \Yii::t("app",'Required');
                $errors[] = \Yii::t("app",
                    'Authentication factors must have a name to identify them.');
            }

            if (!$errors) {
                $factor->setFactorName($name);
                $factor->save();

                $user->updateMultiFactorEnrollment();

                return (new AphrontRedirectResponse())
                    ->setURI($this->getPanelURI('?id=' . $factor->getID()));
            }
        } else {
            $name = $factor->getFactorName();
        }

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setName('name')
                    ->setLabel(\Yii::t("app",'Name'))
                    ->setValue($name)
                    ->setError($e_name));

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->addHiddenInput('edit', $factor->getID())
            ->setTitle(\Yii::t("app",'Edit Authentication Factor'))
            ->setErrors($errors)
            ->appendChild($form->buildLayoutView())
            ->addSubmitButton(\Yii::t("app",'Save'))
            ->addCancelButton($this->getPanelURI());

        return (new AphrontDialogResponse())
            ->setDialog($dialog);
    }

    /**
     * @param AphrontRequest $request
     * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function processDelete(AphrontRequest $request)
    {
        $viewer = $request->getUser();
        $user = $this->getUser();

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $this->getPanelURI());

        $factor = (new PhabricatorAuthFactorConfig())->loadOneWhere(
            'id = %d AND userPHID = %s',
            $request->getInt('delete'),
            $user->getPHID());
        if (!$factor) {
            return new Aphront404Response();
        }

        if ($request->isFormPost()) {
            $factor->delete();

            $log = PhabricatorUserLog::initializeNewLog(
                $viewer,
                $user->getPHID(),
                PhabricatorUserLog::ACTION_MULTI_REMOVE);
            $log->save();

            $user->updateMultiFactorEnrollment();

            return (new AphrontRedirectResponse())
                ->setURI($this->getPanelURI());
        }

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->addHiddenInput('delete', $factor->getID())
            ->setTitle(\Yii::t("app",'Delete Authentication Factor'))
            ->appendParagraph(
                \Yii::t("app",
                    'Really remove the authentication factor %s from your account?',
                    phutil_tag('strong', array(), $factor->getFactorName())))
            ->addSubmitButton(\Yii::t("app",'Remove Factor'))
            ->addCancelButton($this->getPanelURI());

        return (new AphrontDialogResponse())
            ->setDialog($dialog);
    }


}
