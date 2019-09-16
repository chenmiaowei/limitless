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
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\rbac\editors\PhabricatorRBACRoleEditEngine;
use orangins\modules\rbac\editors\PhabricatorRBACRoleEditor;
use orangins\modules\rbac\models\PhabricatorRBACRoleTransaction;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\rbac\typeahead\PhabricatorRBACCapabilityDatasource;
use orangins\modules\rbac\xaction\PhabricatorRBACRoleCapabilitiesTransaction;

/**
 * Class PhabricatorRBACRoleEditAction
 * @package orangins\modules\rbac\actions
 * @author 陈妙威
 */
class PhabricatorRBACRoleAddCapabilityAction extends PhabricatorRBACAction
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
        $id = $request->getURIData('id');
        $title = '添加节点';

        /** @var RbacRole $role */
        $role = RbacRole::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$role) {
            return new Aphront404Response();
        }

        $errors = [];
        if($request->isFormPost()) {
            $capabilities = $request->getArr("capabilities");
            if(empty($capabilities)) {
                $errors[] = \Yii::t("app", "节点不能为空");
            } else {

                $xactions = [];
                $xactions[] = (new PhabricatorRBACRoleTransaction)
                    ->setTransactionType(PhabricatorRBACRoleCapabilitiesTransaction::TRANSACTIONTYPE)
                    ->setNewValue([
                        '+' => $capabilities
                    ]);

                (new PhabricatorRBACRoleEditor())
                    ->setActor($request->getViewer())
                    ->setContinueOnNoEffect(true)
                    ->setContentSourceFromRequest($request)
                    ->applyTransactions($role, $xactions);

                $cache = PhabricatorCaches::getMutableStructureCache();
                $cache->deleteKey(RbacRole::getCapabilityCacheKey());
                return (new AphrontRedirectResponse())->setURI($request->getStr("redirect_uri", $role->getURI()));
            }
        }

        return $this->newDialog()
            ->setErrors($errors)
            ->addClass("wmin-600")
            ->setTitle($title)
            ->appendChild((new AphrontFormTokenizerControl())
                ->setViewer($request->getViewer())
                ->setLabel(\Yii::t("app",'节点列表'))
                ->setName('capabilities')
                ->setDatasource(new PhabricatorRBACCapabilityDatasource()))
            ->addSubmitButton(\Yii::t("app", "Submit"))
            ->addCancelButton(\Yii::t("app", "Cancel"));
    }
}