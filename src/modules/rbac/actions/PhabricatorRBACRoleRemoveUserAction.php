<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 4:49 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\actions;


use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\rbac\editors\PhabricatorRBACRoleEditEngine;
use orangins\modules\rbac\editors\PhabricatorRBACRoleEditor;
use orangins\modules\rbac\models\PhabricatorRBACRoleTransaction;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\rbac\typeahead\PhabricatorRBACCapabilityDatasource;
use orangins\modules\rbac\xaction\PhabricatorRBACRoleCapabilitiesTransaction;
use orangins\modules\rbac\xaction\PhabricatorRBACRoleUsersTransaction;

/**
 * Class PhabricatorRBACRoleEditAction
 * @package orangins\modules\rbac\actions
 * @author 陈妙威
 */
class PhabricatorRBACRoleRemoveUserAction extends PhabricatorRBACAction
{
    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $phid = $request->getURIData('object_phid');
        $title = '删除节点';

        /** @var RbacRole $role */
        $role = RbacRole::find()
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();
        if (!$role) {
            return new Aphront404Response();
        }

        $errors = [];
        if($request->isFormPost()) {
            $capability = $request->getStr("user_phid");
            if(empty($capability)) {
                $errors[] = \Yii::t("app", "未选中用户");
            } else {

                $xactions = [];
                $xactions[] = (new PhabricatorRBACRoleTransaction)
                    ->setTransactionType(PhabricatorRBACRoleUsersTransaction::TRANSACTIONTYPE)
                    ->setNewValue([
                        '-' => [
                            $capability
                        ]
                    ]);

                (new PhabricatorRBACRoleEditor())
                    ->setActor($request->getViewer())
                    ->setContinueOnNoEffect(true)
                    ->setContentSourceFromRequest($request)
                    ->applyTransactions($role, $xactions);

                return (new AphrontRedirectResponse())->setURI($request->getStr("redirect_uri", $role->getURI()));
            }
        }

        return $this->newDialog()
            ->setErrors($errors)
            ->addClass("wmin-600")
            ->setTitle($title)
            ->appendChild(\Yii::t("app", "确定删除当前用户？"))
            ->addSubmitButton(\Yii::t("app", "Submit"))
            ->addCancelButton(\Yii::t("app", "Cancel"));
    }
}