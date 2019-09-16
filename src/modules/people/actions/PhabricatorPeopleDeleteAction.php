<?php

namespace orangins\modules\people\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorPeopleDeleteAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleDeleteAction
    extends PhabricatorPeopleAction
{

    /**
     * @return Aphront404Response|AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $user = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        $manage_uri = $this->getApplicationURI("manage/{$id}/");

        if ($user->getPHID() == $viewer->getPHID()) {
            return $this->buildDeleteSelfResponse($manage_uri);
        }

        $str1 = \Yii::t("app",
            'Be careful when deleting users! This will permanently and ' .
            'irreversibly destroy this user account.');

        $str2 = \Yii::t("app",
            'If this user interacted with anything, it is generally better to ' .
            'disable them, not delete them. If you delete them, it will no longer ' .
            'be possible to (for example) search for objects they created, and you ' .
            'will lose other information about their history. Disabling them ' .
            'instead will prevent them from logging in, but will not destroy any of ' .
            'their data.');

        $str3 = \Yii::t("app",
            'It is generally safe to delete newly created users (and test users and ' .
            'so on), but less safe to delete established users. If possible, ' .
            'disable them instead.');

        $str4 = \Yii::t("app", 'To permanently destroy this user, run this command:');

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendRemarkupInstructions(
                csprintf(
                    "  phabricator/ $ ./bin/remove destroy %R\n",
                    '@' . $user->getUsername()));

        return $this->newDialog()
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->setTitle(\Yii::t("app", 'Permanently Delete User'))
            ->setShortTitle(\Yii::t("app", 'Delete User'))
            ->appendParagraph($str1)
            ->appendParagraph($str2)
            ->appendParagraph($str3)
            ->appendParagraph($str4)
            ->appendChild($form->buildLayoutView())
            ->addCancelButton($manage_uri, \Yii::t("app", 'Close'));
    }

    /**
     * @param $cancel_uri
     * @return AphrontDialogView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function buildDeleteSelfResponse($cancel_uri)
    {
        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'You Shall Journey No Farther'))
            ->appendParagraph(
                \Yii::t("app",
                    'As you stare into the gaping maw of the abyss, something ' .
                    'holds you back.'))
            ->appendParagraph(\Yii::t("app", 'You can not delete your own account.'))
            ->addCancelButton($cancel_uri, \Yii::t("app", 'Turn Back'));
    }


}
