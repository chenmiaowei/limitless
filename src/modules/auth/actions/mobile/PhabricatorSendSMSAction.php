<?php

namespace orangins\modules\auth\actions\mobile;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\AphrontJSONResponse;
use orangins\modules\auth\models\AuthMobileCaptcha;
use UnexpectedValueException;

/**
 * Class PhabricatorAuthNewAction
 * @package orangins\modules\auth\actions\config
 * @author 陈妙威
 */
final class PhabricatorSendSMSAction extends PhabricatorAction
{

    /**
     * @return Aphront400Response|AphrontJSONResponse
     * @throws ActiveRecordException
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $code = strval(rand(123456, 987654));
        $mobile = $request->getStr("mobile");

        if(!preg_match("/[\d]{11}/", $mobile)) {
            throw new UnexpectedValueException('手机号不能拿为空');
        }

        $authMobileCaptcha = new AuthMobileCaptcha();
        $authMobileCaptcha->mobile = $mobile;
        $authMobileCaptcha->captcha = $code;
        $authMobileCaptcha->ip = \Yii::$app->request->getRemoteIP();
        $authMobileCaptcha->is_expired = 0;
        $authMobileCaptcha->expired_at = strtotime("+30 minutes");
        if (!$authMobileCaptcha->save()) {
            throw new ActiveRecordException("验证码发送失败", $authMobileCaptcha->getErrorSummary(true));
        }

        \Requests::post("http://v.juhe.cn/sms/send", [], [
            "mobile" => $mobile,
            "tpl_id" => "101129",
            "tpl_value" => "#code#={$code}",
            "key" => "2f5b95694b3d081b312552e90d585198",
        ]);

        return (new AphrontJSONResponse())->setContent([]);
    }
}
