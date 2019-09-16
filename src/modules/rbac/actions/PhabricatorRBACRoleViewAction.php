<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 4:49 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\actions;


use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\rbac\models\PhabricatorRBACRoleTransaction;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\rbac\models\RbacRoleCapability;
use orangins\modules\rbac\models\RbacUser;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorRBACRoleViewAction
 * @package orangins\modules\rbac\actions
 * @author 陈妙威
 */
class PhabricatorRBACRoleViewAction extends PhabricatorRBACAction
{
    /**
     * @var RbacRole
     */
    private $role;

    /**
     * @return RbacRole
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param RbacRole $role
     * @return self
     */
    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        /** @var RbacRole $role */
        $role = RbacRole::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$role) {
            return new Aphront404Response();
        }
        $this->setRole($role);

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $role,
            PhabricatorPolicyCapability::CAN_EDIT);

        $title = $role->name;
        $crumbs = $this->buildApplicationCrumbs();
        $header = $this->buildHeaderView();

        $curtain = $this->buildCurtainView($role);

        $timeline = $this->buildTransactionTimeline(
            $role,
            PhabricatorRBACRoleTransaction::find());
        $timeline->setShouldTerminate(true);

        $renderRole = $this->newCapabilityView($role);
        $renderUser = $this->newUserView($viewer, $role);

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $renderRole,
                    $renderUser,
                    $timeline,
                ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param RbacRole $role
     * @return PHUIObjectBoxView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function newUserView(PhabricatorUser $viewer, RbacRole $role)
    {
        $rows = [];
        $rbacUsers = RbacUser::find()->andWhere(['object_phid' => $role->getPHID()])->all();
        $column = ArrayHelper::getColumn($rbacUsers, 'user_phid', []);

        /** @var PhabricatorUser[] $users */
        $users = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withPHIDs($column)
            ->execute();

        foreach ($users as $user) {
            $rows[] = [
                $user->getUsername(),
                $user->getRealName(),
                (new PHUIButtonView())
                    ->setTag("a")
                    ->setText("删除")
                    ->setWorkflow(true)
                    ->setSize("btn-xs")
                    ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                    ->setHref(Url::to(['/rbac/role/remove-user'
                        , 'object_phid' => $role->getPHID()
                        , 'user_phid' => $user->getPHID()
                    ]))
            ];
        }


        $usage_table = (new AphrontTableView($rows))
            ->setNoDataString(
                \Yii::t("app",'当前角色没有任何的用户.'))
            ->setHeaders(
                array(
                    \Yii::t("app",'用户名'),
                    \Yii::t("app",'真是名称'),
                    \Yii::t("app",'操作'),
                ))
            ->setColumnClasses(
                array(
                    '',
                    '',
                    'w-25 text-right',
                ));

        $header_view = (new PHUIHeaderView())
//            ->addActionLink((new PHUIButtonView())
//                ->setTag("a")
//                ->setText("添加用户")
//                ->setWorkflow(true)
//                ->setSize("btn-xs")
//                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
//                ->setHref(Url::to(['/rbac/role/add-user'
//                    , 'id' => $role->getID()
//                ])))
            ->setHeader(\Yii::t("app",'用户列表'));

        $usage_box = (new PHUIObjectBoxView())
            ->setTable($usage_table)
            ->setHeader($header_view);

        return $usage_box;
    }

    /**
     * @param RbacRole $role
     * @return PHUIObjectBoxView
     * @throws \Exception
     * @author 陈妙威
     */
    private function newCapabilityView(RbacRole $role)
    {
        $rows = [];
        $phabricatorPolicyCapabilities = PhabricatorPolicyCapability::getCapabilityMap();
        $rbacRoleCapabilities = RbacRoleCapability::find()->andWhere(['object_phid' => $role->getPHID()])->all();
        foreach ($rbacRoleCapabilities as $rbacRoleCapability) {
            $capability = $rbacRoleCapability->capability;
            $phabricatorPolicyCapability = $phabricatorPolicyCapabilities[$capability];
            $rows[] = [
                $phabricatorPolicyCapability->getCapabilityKey(),
                $phabricatorPolicyCapability->getCapabilityName(),
                (new PHUIButtonView())
                    ->setTag("a")
                    ->setText("删除")
                    ->setWorkflow(true)
                    ->setSize("btn-xs")
                    ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                    ->setHref(Url::to(['/rbac/role/remove-capability'
                        , 'object_phid' => $role->getPHID()
                        , 'capability' => $capability
                    ]))
            ];
        }


        $usage_table = (new AphrontTableView($rows))
            ->setNoDataString(
                \Yii::t("app",'当前角色没有任何的节点.'))
            ->setHeaders(
                array(
                    \Yii::t("app",'节点名称'),
                    \Yii::t("app",'节点介绍'),
                    \Yii::t("app",'操作'),
                ))
            ->setColumnClasses(
                array(
                    '',
                    '',
                    'w-25 text-right',
                ));

        $header_view = (new PHUIHeaderView())
            ->addActionLink((new PHUIButtonView())
                ->setTag("a")
                ->setText("添加节点")
                ->setWorkflow(true)
                ->setSize("btn-xs")
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                ->setHref(Url::to(['/rbac/role/add-capability'
                    , 'id' => $role->getID()
                ])))
            ->setHeader(\Yii::t("app",'角色节点'));

        $usage_box = (new PHUIObjectBoxView())
            ->setTable($usage_table)
            ->setHeader($header_view);

        return $usage_box;
    }

    /**
     * @param RbacRole $dashboard
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(RbacRole $dashboard)
    {
        $viewer = $this->getViewer();
        $id = $dashboard->getID();

        $curtain = $this->newCurtainView($dashboard);

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $dashboard,
            PhabricatorPolicyCapability::CAN_EDIT);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'编辑权限'))
                ->setIcon('fa-pencil')
                ->setHref(Url::to(['/rbac/role/edit', 'id' => $id]))
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit));


        if ($dashboard->isArchived()) {
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app",'启用权限'))
                    ->setIcon('fa-check')
                    ->setHref(Url::to(['/rbac/role/active', 'id' => $id]))
                    ->setDisabled(!$can_edit)
                    ->setWorkflow(true));
        } else {
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app",'归档权限'))
                    ->setIcon('fa-ban')
                    ->setHref(Url::to(['/rbac/role/archive', 'id' => $id]))
                    ->setDisabled(!$can_edit)
                    ->setWorkflow(true));
        }
        return $curtain;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function buildHeaderView()
    {
        $viewer = $this->getViewer();
        $dashboard = $this->getRole();


        return (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setHeader($dashboard->name)
            ->setPolicyObject($dashboard);
    }
}