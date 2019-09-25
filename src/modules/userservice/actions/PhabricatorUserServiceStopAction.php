<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 11:36 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\userservice\capability\UserServiceFinanceCapability;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditor;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\models\PhabricatorUserServiceTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceStatusTransaction;
use Yii;

/**
 * Class PhabricatorUserServicePublishAction
 * @package orangins\modules\userservice\actions
 * @author 陈妙威
 */
class PhabricatorUserServiceStopAction extends PhabricatorUserServiceAction
{
    /**
     * @author 陈妙威
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function run()
    {
        $request = $this->getRequest();
        $this->requireApplicationCapability(UserServiceFinanceCapability::CAPABILITY);
        $id = $request->getInt('id');
        /** @var PhabricatorUserService $dashboard */
        $dashboard = PhabricatorUserService::find()
            ->setViewer($request->getViewer())
            ->withIDs(array($id))
            ->executeOne();
        if (!$dashboard) {
            return new Aphront404Response();
        }

        if ($request->isFormPost()) {

            $xactions = array();
            $xactions[] = (new PhabricatorUserServiceTransaction)
                ->setTransactionType(PhabricatorUserServiceStatusTransaction::TRANSACTIONTYPE)
                ->setNewValue(PhabricatorUserService::STATUS_STOPPED);

            (new PhabricatorUserServiceEditor())
                ->setActor($request->getViewer())
                ->setContinueOnNoEffect(true)
                ->setContentSourceFromRequest($request)
                ->applyTransactions($dashboard, $xactions);
            return (new AphrontRedirectResponse())->setURI($this->getRequest()->getStr('redirect_uri'));
        }
        $title = \Yii::t("app", '删除服务');
        return $this->newDialog()
            ->addClass("wmin-600")
            ->setTitle($title)
            ->appendChild(Yii::t("app", "您确定停用当前服务？"))
            ->addSubmitButton(\Yii::t("app", "Submit"))
            ->addCancelButton(\Yii::t("app", "Cancel"));
    }
}