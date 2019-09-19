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
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\conduit\typeahead\PhabricatorConduitCompositeDatasource;
use orangins\modules\userservice\capability\UserServiceFinanceCapability;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditor;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\models\PhabricatorUserServiceTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceAPITransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorUserServicePublishAction
 * @package orangins\modules\userservice\actions
 * @author 陈妙威
 */
class PhabricatorUserServiceEditAction extends PhabricatorUserServiceAction
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
     * @throws \yii\base\InvalidConfigException*@throws \Exception
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

        $errors = [];
        $apis = ArrayHelper::getValue($dashboard->getParameters(), 'apis', []);
        if ($request->isFormPost()) {
            $apis = $request->getArr("apis");
            if (count($apis) < 1) {
                $errors[] = "请输入接口";
            } else {
                $xactions = array();
                $xactions[] = (new PhabricatorUserServiceTransaction)
                    ->setTransactionType(PhabricatorUserServiceAPITransaction::TRANSACTIONTYPE)
                    ->setNewValue($apis);
                (new PhabricatorUserServiceEditor())
                    ->setActor($request->getViewer())
                    ->setContinueOnNoEffect(true)
                    ->setContentSourceFromRequest($request)
                    ->applyTransactions($dashboard, $xactions);
                return (new AphrontRedirectResponse())->setURI($this->getApplicationURI('index/query'));
            }
        }
        $title = \Yii::t("app",'修改服务');
        return $this->newDialog()
            ->addClass("wmin-600")
            ->setTitle($title)
            ->appendChild((new AphrontFormTokenizerControl())
                ->setViewer($request->getViewer())
                ->setLabel(\Yii::t("app",'接口列表'))
                ->setName('apis')
                ->setValue($apis)
                ->setDatasource(new PhabricatorConduitCompositeDatasource()))
            ->addSubmitButton(\Yii::t("app", "Submit"))
            ->addCancelButton(\Yii::t("app", "Cancel"));
    }
}