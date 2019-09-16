<?php

namespace orangins\modules\people\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\people\capability\PeopleDisableUsersCapability;
use orangins\modules\people\editors\PhabricatorUserTransactionEditor;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserTransaction;
use orangins\modules\people\xaction\PhabricatorUserDisableTransaction;

/**
 * Class PhabricatorPeopleDisableAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleDisableAction
    extends PhabricatorPeopleAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return false;
    }

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
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException*@throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $id = $request->getURIData('id');
        $via = $request->getURIData('via');

        $user = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        // NOTE: We reach this controller via the administrative "Disable User"
        // on profiles and also via the "X" action on the approval queue. We do
        // things slightly differently depending on the context the actor is in.

        // In particular, disabling via "Disapprove" requires you be an
        // administrator (and bypasses the "Can Disable Users" permission).
        // Disabling via "Disable" requires the permission only.

        $is_disapprove = ($via == 'disapprove');
        if ($is_disapprove) {
            $done_uri = $this->getApplicationURI('query/approval/');

            if (!$viewer->getIsAdmin()) {
                return $this->newDialog()
                    ->setTitle(\Yii::t("app", 'No Permission'))
                    ->appendParagraph(\Yii::t("app", 'Only administrators can disapprove users.'))
                    ->addCancelButton($done_uri);
            }

            if ($user->getIsApproved()) {
                return $this->newDialog()
                    ->setTitle(\Yii::t("app", 'Already Approved'))
                    ->appendParagraph(\Yii::t("app", 'This user has already been approved.'))
                    ->addCancelButton($done_uri);
            }

            // On the "Disapprove" flow, bypass the "Can Disable Users" permission.
            $actor = PhabricatorUser::getOmnipotentUser();
            $should_disable = true;
        } else {
            $this->requireApplicationCapability(
                PeopleDisableUsersCapability::CAPABILITY);

            $actor = $viewer;
            $done_uri = $this->getApplicationURI("manage/{$id}/");
            $should_disable = !$user->getIsDisabled();
        }

        if ($viewer->getPHID() == $user->getPHID()) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Something Stays Your Hand'))
                ->appendParagraph(
                    \Yii::t("app",
                        'Try as you might, you find you can not disable your own account.'))
                ->addCancelButton($done_uri, \Yii::t("app", 'Curses!'));
        }

        if ($request->isFormPost()) {
            $xactions = array();

            $xactions[] = (new PhabricatorUserTransaction())
                ->setTransactionType(PhabricatorUserDisableTransaction::TRANSACTIONTYPE)
                ->setNewValue($should_disable);

            (new PhabricatorUserTransactionEditor())
                ->setActor($actor)
                ->setActingAsPHID($viewer->getPHID())
                ->setContentSourceFromRequest($request)
                ->setContinueOnMissingFields(true)
                ->setContinueOnNoEffect(true)
                ->applyTransactions($user, $xactions);

            return (new AphrontRedirectResponse())->setURI($done_uri);
        }

        if ($should_disable) {
            $title = \Yii::t("app", 'Disable User?');
            $short_title = \Yii::t("app", 'Disable User');

            $body = \Yii::t("app",
                'Disable %s? They will no longer be able to access Phabricator or ' .
                'receive email.',
                phutil_tag('strong', array(), $user->getUsername()));

            $submit = \Yii::t("app", 'Disable User');
        } else {
            $title = \Yii::t("app", 'Enable User?');
            $short_title = \Yii::t("app", 'Enable User');

            $body = \Yii::t("app",
                'Enable %s? They will be able to access Phabricator and receive ' .
                'email again.',
                phutil_tag('strong', array(), $user->getUsername()));

            $submit = \Yii::t("app", 'Enable User');
        }

        return $this->newDialog()
            ->setTitle($title)
            ->setShortTitle($short_title)
            ->appendParagraph($body)
            ->addCancelButton($done_uri)
            ->addSubmitButton($submit);
    }

}
