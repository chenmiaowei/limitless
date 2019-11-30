<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019-11-30
 * Time: 13:45
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\uiexample\actions\form;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\response\AphrontAjaxResponse;

/**
 * Class FormTextCaptchaAction
 * @package orangins\modules\uiexample\actions\form
 * @author 陈妙威
 */
class FormTextCaptchaAction extends PhabricatorAction
{
    /**
     * @author 陈妙威
     */
    public function run()
    {
        return (new AphrontAjaxResponse())->setContent([]);
    }
}