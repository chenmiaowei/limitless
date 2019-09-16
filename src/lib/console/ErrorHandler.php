<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/6/12
 * Time: 12:10 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\console;

/**
 * Class ErrorHandler
 * @package orangins\lib\console
 * @author 陈妙威
 */
class ErrorHandler extends \yii\console\ErrorHandler
{
    /**
     * @var bool
     */
    public $discardExistingOutput = false;
}