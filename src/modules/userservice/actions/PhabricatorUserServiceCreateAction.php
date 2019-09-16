<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/7
 * Time: 11:48 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\actions;


use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormDatePickerControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\lib\view\layout\PHUICardView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\typeahead\PhabricatorPeopleUserFunctionDatasource;
use orangins\modules\typeahead\typeahead\PhabricatorConduitCompositeDatasource;
use orangins\modules\userservice\capability\UserServiceFinanceCapability;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditor;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\models\PhabricatorUserServiceTransaction;
use orangins\modules\userservice\servicetype\PhabricatorUserServiceType;
use orangins\modules\userservice\xaction\PhabricatorUserServiceAmountTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceAPITransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceExpireTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceTimesTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceUserTransaction;

/**
 * Class PhabricatorUserServiceListAction
 * @package orangins\modules\userservice\actions
 * @author 陈妙威
 */
class PhabricatorUserServiceCreateAction extends PhabricatorUserServiceAction
{
    /**
     * @return mixed
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
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $this->requireApplicationCapability(UserServiceFinanceCapability::CAPABILITY);

        $request = $this->getRequest();
        $type = $request->getStr("type");
        if (!$type) {
            $types = PhabricatorUserServiceType::getAllTypes();
            $buttons = [];
            foreach ($types as $type) {
                $buttons[] = JavelinHtml::phutil_tag_div("col-lg-6", (new PHUICardView())
                    ->addClass("m-0")
                    ->setHref($this->getApplicationURI("index/create", ['type' => $type->getKey()]))
                    ->setText($type->getName())
                    ->setIcon($type->getIcon()));
            }
            $view = JavelinHtml::phutil_tag_div("row", $buttons);

            return $this->newDialog()
                ->setTitle("选择计费类型")
                ->appendChild($view)
                ->addCancelButton(\Yii::t("app", "Cancel"));
        } else {
            $type = $request->getStr("type");
            if ($type === "api.time") {
                $errors = [];
                $times = null;
                $userPHID = [];
                $apis = [];
                $expire = null;
                if ($request->isFormPost()) {
                    $userPHID = $request->getArr("user_phid");
                    $apis = $request->getArr("apis");
                    $expire = $request->getStr("expire");
                    $times = $request->getStr("times");

                    if (count($userPHID) !== 1) {
                        $errors[] = "请输入用户";
                    } else if (count($apis) < 1) {
                        $errors[] = "请输入接口";
                    } else if (intval($times) <= 0) {
                        $errors[] = "请输入次数";
                    } else if (strtotime($expire) < time()) {
                        $errors[] = "请输入正确时间";
                    } else {
                        $dashboard = PhabricatorUserService::initializeNewUserService($request->getViewer());
                        $dashboard->type = $type;

                        $xactions = array();

                        $xactions[] = (new PhabricatorUserServiceTransaction())
                            ->setTransactionType(PhabricatorUserServiceUserTransaction::TRANSACTIONTYPE)
                            ->setNewValue(head($userPHID));

                        $xactions[] = (new PhabricatorUserServiceTransaction)
                            ->setTransactionType(PhabricatorUserServiceExpireTransaction::TRANSACTIONTYPE)
                            ->setNewValue(strtotime($expire));

                        $xactions[] = (new PhabricatorUserServiceTransaction)
                            ->setTransactionType(PhabricatorUserServiceTimesTransaction::TRANSACTIONTYPE)
                            ->setNewValue($times);

                        $xactions[] = (new PhabricatorUserServiceTransaction)
                            ->setTransactionType(PhabricatorUserServiceAPITransaction::TRANSACTIONTYPE)
                            ->setNewValue($apis);

                        $editor = (new PhabricatorUserServiceEditor())
                            ->setActor($request->getViewer())
                            ->setContinueOnNoEffect(true)
                            ->setContentSourceFromRequest($request)
                            ->applyTransactions($dashboard, $xactions);

                        return (new AphrontRedirectResponse())->setURI($this->getApplicationURI('index/query'));
                    }
                }
                $form = (new AphrontFormView())
                    ->setUser($request->getViewer());


                $form->appendChild(
                    (new AphrontFormTokenizerControl())
                        ->setViewer($request->getViewer())
                        ->setLabel(\Yii::t("app", '用户'))
                        ->setName('user_phid')
                        ->setLimit(1)
                        ->setValue($userPHID)
                        ->setDatasource(new PhabricatorPeopleUserFunctionDatasource()));
                $form->appendChild(
                    (new AphrontFormTokenizerControl())
                        ->setViewer($request->getViewer())
                        ->setLabel(\Yii::t("app", '接口列表'))
                        ->setName('apis')
                        ->setValue($apis)
                        ->setDatasource(new PhabricatorConduitCompositeDatasource()));

                $form->appendChild(
                    (new AphrontFormTextControl())
                        ->setViewer($request->getViewer())
                        ->setLabel("查询次数")
                        ->setName('times')
                        ->setValue($times));

                $form->appendChild(
                    (new AphrontFormDatePickerControl())
                        ->setViewer($request->getViewer())
                        ->setLabel("过期时间")
                        ->setName('expire')
                        ->setValue($expire));

                $form->appendChild(
                    (new AphrontFormSubmitControl())
                        ->addCancelButton($this->getApplicationURI('index/query'))
                        ->setValue(\Yii::t("app", '创建服务')));

                $box = (new PHUIObjectBoxView())
                    ->setHeaderText(\Yii::t("app", '创建服务'))
                    ->setFormErrors($errors)
                    ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
                    ->setForm($form);

                $title = \Yii::t("app", '创建服务');

                $crumbs = $this->buildApplicationCrumbs();
                $crumbs->addTextCrumb(\Yii::t("app", '新的服务'));
                $crumbs->setBorder(true);

                $header = (new PHUIPageHeaderView())
                    ->setHeader($title)
                    ->setHeaderIcon('fa-plus-square');

                $view = (new PHUITwoColumnView())
                    ->setFooter($box);

                return $this->newPage()
                    ->setHeader($header)
                    ->setTitle($title)
                    ->setCrumbs($crumbs)
                    ->appendChild($view);


            } else {
                $errors = [];
                $userPHID = [];
                $apis = [];
                $amount = null;
                if ($request->isFormPost()) {
                    $userPHID = $request->getArr("user_phid");
                    $amount = $request->getStr("amount");
                    $apis = $request->getArr("apis");

                    if (count($userPHID) !== 1) {
                        $errors[] = "请输入用户";
                    } else if (count($apis) < 1) {
                        $errors[] = "请输入接口";
                    } else if (!preg_match("/^-?(?:\d+|\d*\.\d+)$/", $amount, $match) || OranginsUtil::compareFloatNumbers($amount, 0, "<=")) {
                        $errors[] = "请输入正确金额";
                    } else {
                        $dashboard = PhabricatorUserService::initializeNewUserService($request->getViewer());
                        $dashboard->type = $type;

                        $xactions = array();

                        $xactions[] = (new PhabricatorUserServiceTransaction())
                            ->setTransactionType(PhabricatorUserServiceUserTransaction::TRANSACTIONTYPE)
                            ->setNewValue(head($userPHID));

                        $xactions[] = (new PhabricatorUserServiceTransaction)
                            ->setTransactionType(PhabricatorUserServiceAmountTransaction::TRANSACTIONTYPE)
                            ->setNewValue($amount);

                        $xactions[] = (new PhabricatorUserServiceTransaction)
                            ->setTransactionType(PhabricatorUserServiceAPITransaction::TRANSACTIONTYPE)
                            ->setNewValue($apis);

                        $editor = (new PhabricatorUserServiceEditor())
                            ->setActor($request->getViewer())
                            ->setContinueOnNoEffect(true)
                            ->setContentSourceFromRequest($request)
                            ->applyTransactions($dashboard, $xactions);

                        return (new AphrontRedirectResponse())->setURI($this->getApplicationURI('index/query'));
                    }
                }
                $form = (new AphrontFormView())
                    ->setUser($request->getViewer());


//            $phabricatorUserServiceTypes = PhabricatorUserServiceType::getAllTypes();
//            $phabricatorUserServiceTypes = mpull($phabricatorUserServiceTypes, null, "getKey");
//            /** @var PhabricatorUserServiceType $phabricatorUserServiceType */
//            $phabricatorUserServiceType = $phabricatorUserServiceTypes[$type];
                $form->appendChild(
                    (new AphrontFormTokenizerControl())
                        ->setViewer($request->getViewer())
                        ->setLabel(\Yii::t("app", '用户'))
                        ->setName('user_phid')
                        ->setLimit(1)
                        ->setValue($userPHID)
                        ->setDatasource(new PhabricatorPeopleUserFunctionDatasource()));
                $form->appendChild(
                    (new AphrontFormTokenizerControl())
                        ->setViewer($request->getViewer())
                        ->setLabel(\Yii::t("app", '接口列表'))
                        ->setName('apis')
                        ->setValue($apis)
                        ->setDatasource(new PhabricatorConduitCompositeDatasource()));

                $form->appendChild(
                    (new AphrontFormTextControl())
                        ->setViewer($request->getViewer())
                        ->setLabel("充值金额")
                        ->setName('amount')
                        ->setValue($amount));


                $form->appendChild(
                    (new AphrontFormSubmitControl())
                        ->addCancelButton($this->getApplicationURI('index/query'))
                        ->setValue(\Yii::t("app", '创建服务')));

                $box = (new PHUIObjectBoxView())
                    ->setHeaderText(\Yii::t("app", '创建服务'))
                    ->setFormErrors($errors)
                    ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
                    ->setForm($form);

                $title = \Yii::t("app", '创建服务');

                $crumbs = $this->buildApplicationCrumbs();
                $crumbs->addTextCrumb(\Yii::t("app", '新的服务'));
                $crumbs->setBorder(true);

                $header = (new PHUIPageHeaderView())
                    ->setHeader($title)
                    ->setHeaderIcon('fa-plus-square');

                $view = (new PHUITwoColumnView())
                    ->setFooter($box);

                return $this->newPage()
                    ->setHeader($header)
                    ->setTitle($title)
                    ->setCrumbs($crumbs)
                    ->appendChild($view);
            }
        }
    }
}