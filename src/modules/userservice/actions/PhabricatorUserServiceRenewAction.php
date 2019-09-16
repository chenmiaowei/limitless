<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/7
 * Time: 11:48 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\actions;


use orangins\lib\helpers\OranginsUtil;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\control\AphrontFormDatePickerControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\userservice\capability\UserServiceFinanceCapability;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditor;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\models\PhabricatorUserServiceTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceAmountTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceExpireTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceTimesTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorUserServiceListAction
 * @package orangins\modules\userservice\actions
 * @author 陈妙威
 */
class PhabricatorUserServiceRenewAction extends PhabricatorUserServiceAction
{
    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $this->requireApplicationCapability(UserServiceFinanceCapability::CAPABILITY);
        $request = $this->getRequest();

        $id = $request->getInt('id');
        /** @var PhabricatorUserService $dashboard */
        $dashboard = PhabricatorUserService::find()
            ->setViewer($request->getViewer())
            ->withIDs(array($id))
            ->executeOne();
        if (!$dashboard) {
            return new Aphront404Response();
        } else {
            $expire = date("Y-m-d", ArrayHelper::getValue($dashboard->getParameters(), "expire"));
            $times = ArrayHelper::getValue($dashboard->getParameters(), "times");
            $errors = [];
            if ($request->isFormPost()) {
                $expire = $request->getStr('expire');
                $times = $request->getInt('times');
                if (strtotime($expire) < time()) {
                    $errors[] = "请输入正确的时间";
                } else if (intval($times) <= 0) {
                    $errors[] = "请输入正确的次数";
                } else {


                    $dashboard->openTransaction();

                    try {
                        $command = $dashboard->getDb()->createCommand("SELECT * FROM " . $dashboard::tableName() . " WHERE id=:id FOR UPDATE", [
                            ":id" => $dashboard->id
                        ]);
                        $command->queryOne();

                        $xactions[] = (new PhabricatorUserServiceTransaction)
                            ->setTransactionType(PhabricatorUserServiceExpireTransaction::TRANSACTIONTYPE)
                            ->setNewValue(strtotime($expire));

                        $xactions[] = (new PhabricatorUserServiceTransaction)
                            ->setTransactionType(PhabricatorUserServiceTimesTransaction::TRANSACTIONTYPE)
                            ->setNewValue($times);

                        (new PhabricatorUserServiceEditor())
                            ->setActor($request->getViewer())
                            ->setContinueOnNoEffect(true)
                            ->setContentSourceFromRequest($request)
                            ->applyTransactions($dashboard, $xactions);

                        $dashboard->saveTransaction();
                        return (new AphrontRedirectResponse())->setURI($request->getStr('redirect_uri'));
                    } catch (\Exception $e) {
                        \Yii::error($e);
                        $dashboard->killTransaction();
                        $errors[] = "充值失败";
                    }
                }
            }
        }

        return $this->newDialog()
            ->addClass("wmin-600")
            ->setErrors($errors)
            ->appendChild((new AphrontFormDatePickerControl())
                ->setViewer($request->getViewer())
                ->setLabel(\Yii::t("app", '过期时间'))
                ->setName("expire")
                ->setValue($expire))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setViewer($request->getViewer())
                    ->setLabel("查询次数")
                    ->setName('times')
                    ->setValue($times))
            ->setTitle(\Yii::t("app", "充值"))
            ->addSubmitButton(\Yii::t("app", "Submit"))
            ->addCancelButton(\Yii::t("app", "Cancel"));
    }
}