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
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\rbac\editors\PhabricatorRBACRoleEditor;
use orangins\modules\rbac\models\PhabricatorRBACRoleTransaction;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\rbac\xaction\PhabricatorRBACRoleCapabilitiesTransaction;

/**
 * Class PhabricatorRBACRoleEditAction
 * @package orangins\modules\rbac\actions
 * @author 陈妙威
 */
class PhabricatorRBACRoleRemoveCapabilityAction extends PhabricatorRBACAction
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
            $capability = $request->getStr("capability");
            if(empty($capability)) {
                $errors[] = \Yii::t("app", "未选中节点");
            } else {

                $xactions = [];
                $xactions[] = (new PhabricatorRBACRoleTransaction)
                    ->setTransactionType(PhabricatorRBACRoleCapabilitiesTransaction::TRANSACTIONTYPE)
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

                $cache = PhabricatorCaches::getMutableStructureCache();
                $cache->deleteKey(RbacRole::getCapabilityCacheKey());
                return (new AphrontRedirectResponse())->setURI($role->getURI());
            }
        }

        return $this->newDialog()
            ->setErrors($errors)
            ->addClass("wmin-600")
            ->setTitle($title)
            ->appendChild(\Yii::t("app", "确定删除当前节点？"))
            ->addSubmitButton(\Yii::t("app", "Submit"))
            ->addCancelButton(\Yii::t("app", "Cancel"));
    }
}