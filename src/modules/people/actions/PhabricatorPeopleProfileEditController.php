<?php

namespace orangins\modules\people\actions;

use AphrontObjectMissingQueryException;
use AphrontQueryException;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\editors\PhabricatorUserTransactionEditor;
use orangins\modules\people\engine\PhabricatorPeopleProfileMenuEngine;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\IntegrityException;

/**
 * Class PhabricatorPeopleProfileEditController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleProfileEditController
    extends PhabricatorPeopleProfileAction
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|PhabricatorStandardPageView
     * @throws InvalidConfigException *@throws \Exception
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws AphrontObjectMissingQueryException
     * @throws AphrontQueryException
     * @throws Throwable
     * @throws PhabricatorDataNotAttachedException
     * @throws IntegrityException
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
            ->needProfileImage(true)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        $this->setUser($user);

        $done_uri = $this->getApplicationURI("index/manage", ['id' => $id]);

        $field_list = PhabricatorCustomField::getObjectFields(
            $user,
            PhabricatorCustomField::ROLE_EDIT);
        $field_list
            ->setViewer($viewer)
            ->readFieldsFromStorage($user);

        $validation_exception = null;
        if ($request->isFormPost()) {
            $xactions = $field_list->buildFieldTransactionsFromRequest(
                new PhabricatorUserTransaction(),
                $request);

            $editor = (new PhabricatorUserTransactionEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true);

            try {
                $editor->applyTransactions($user, $xactions);
                return (new AphrontRedirectResponse())->setURI($done_uri);
            } catch (PhabricatorApplicationTransactionValidationException $ex) {
                $validation_exception = $ex;
            }
        }

        $title = Yii::t("app", 'Edit Profile');

        $form = (new AphrontFormView())
            ->setUser($viewer);

        $field_list->appendFieldsToForm($form);
        $form
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($done_uri)
                    ->setValue(Yii::t("app", 'Save Profile')));

        $allow_public = PhabricatorEnv::getEnvConfig('policy.allow-public');
        $note = null;
        if ($allow_public) {
            $note = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
                ->appendChild(Yii::t("app",
                    'Information on user profiles on this install is publicly ' .
                    'visible.'));
        }

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(Yii::t("app", 'Profile'))
            ->setValidationException($validation_exception)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(Yii::t("app", 'Edit Profile'));
        $crumbs->setBorder(true);

        $nav = $this->buildNavigation(
            $user,
            PhabricatorPeopleProfileMenuEngine::ITEM_MANAGE);

        $header = (new PHUIPageHeaderView())
            ->setHeader(Yii::t("app", 'Edit Profile: {0}', [$user->getFullName()]))
            ->setHeaderIcon('fa-pencil');

        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $note,
                $form_box,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->setNavigation($nav)
            ->appendChild($view);
    }
}
