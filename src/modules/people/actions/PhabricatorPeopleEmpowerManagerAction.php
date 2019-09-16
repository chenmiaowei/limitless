<?php

namespace orangins\modules\people\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\rbac\models\RbacUser;
use orangins\modules\rbac\typeahead\PhabricatorRBACNodeDatasource;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPeopleEmpowerAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleEmpowerManagerAction
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

        /** @var PhabricatorUser $user */
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
                        'status as an manager.'))
                ->addCancelButton($done_uri, \Yii::t("app", 'Accept Fate'));
        }

        $rbacSettings = $user->getRbacSettings();
        $nodes = ArrayHelper::getValue($rbacSettings, 'user.nodes', []);

        $errors = [];
        if ($request->isFormPost()) {
            $nodes = $request->getArr('nodes');
            (new PhabricatorUserEditor())
                ->setActor($viewer)
                ->makeManagerUser($user, count($nodes) !== 0, $nodes);
            return (new AphrontRedirectResponse())->setURI($done_uri);
        }

        if ($user->getIsManager()) {
            $title = \Yii::t("app", 'Edit Manager');
            $short = \Yii::t("app", 'Edit Manager');
            $body = \Yii::t("app", '编辑管理员权限组，当权限组为空时，自动降为非管理员。');
            $submit = \Yii::t("app", 'Remove Manager');
        } else {
            $title = \Yii::t("app", 'Make Manager?');
            $short = \Yii::t("app", 'Make Manager');
            $body = \Yii::t("app",
                'Empower {0} as an manager? They will be able to create users, ' .
                'approve users, make and remove managers, delete accounts, and ' .
                'perform other administrative functions on this Phabricator install.', [
                    phutil_tag('strong', array(), $user->getUsername())
                ]);
            $submit = \Yii::t("app", 'Make Manager');
        }


        return $this->newDialog()
            ->setErrors($errors)
            ->addClass("wmin-600")
            ->setTitle($title)
            ->setShortTitle($short)
            ->appendParagraph(new \PhutilSafeHTML($body))
            ->appendChild((new AphrontFormTokenizerControl())
                ->setViewer($request->getViewer())
                ->setLabel(\Yii::t("app", '权限列表'))
                ->setName('nodes')
                ->setValue($nodes)
                ->setDatasource(new PhabricatorRBACNodeDatasource()))
            ->addCancelButton($done_uri)
            ->addSubmitButton($submit);
    }
}
