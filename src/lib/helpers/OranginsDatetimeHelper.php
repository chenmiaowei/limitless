<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/7
 * Time: 12:45 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\helpers;


use orangins\lib\env\PhabricatorEnv;

/**
 * Class OranginsDatetimeHelper
 * @package orangins\lib\helpers
 * @author 陈妙威
 */
class OranginsDatetimeHelper
{
    /**
     * @param $string
     * @return false|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function format($string)
    {
        $datatime = date(PhabricatorEnv::getEnvConfig("orangins.date-format") . " " . PhabricatorEnv::getEnvConfig("orangins.time-format"), strtotime($string));

        return $datatime;
    }
}