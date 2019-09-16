<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/14
 * Time: 11:12 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\finance\actions;


use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use yii\helpers\Url;

/**
 * Class FinanceDashboardAction
 * @package orangins\modules\finance\actions
 * @author 陈妙威
 */
class FinanceDepositAction extends FinanceAction
{
    /**
     * @return \orangins\lib\view\AphrontDialogView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $title = \Yii::t("app", "Deposit");


        return $this->newDialog()
            ->setTitle($title)
            ->addCancelButton("#")
            ->appendChild(\Yii::t("app", "暂时不支持自助充值，请联系商务。"));
    }
}