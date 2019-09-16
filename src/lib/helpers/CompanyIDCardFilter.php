<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/7
 * Time: 10:51 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\helpers;


/**
 * Class CompanyIDCardFilter
 * @package orangins\lib\helpers
 * @author 陈妙威
 */
class CompanyIDCardFilter
{
    /**
     * @var int
     */
    public $lenght = 18;//长度

    /**
     * @var string
     */
    public $test = '0123456789ABCDEFGHJKLMNPQRTUWXY';//可以出现的字符

    /**
     * @var string
     */
    public $notest = 'IOZSV';//不会出现的字符


    /**
     * @param $str
     * @return string
     * @author 陈妙威
     */
    function check_group($str)
    {

        $one = '159Y';//第一位可以出现的字符

        $two = '12391';//第二位可以出现的字符

        $str = strtoupper($str);

        if (!strstr($one, $str[1]) && !strstr($two, $str[2]) && !empty($array[substr($str, 2, 6)])) {

            return false;

        } else {
            return true;
        }
//
//        $wi = array(1, 3, 9, 27, 19, 26, 16, 17, 20, 29, 25, 13, 8, 24, 10, 30, 28);//加权因子数值
//
//        $str_organization = substr($str, 0, 17);
//
//        $num = 0;
//
//        for ($i = 0; $i < 17; $i++) {
//
//            $num += $this->transformation($str_organization[$i]) * $wi[$i];
//
//        }
//
//        switch ($num % 31) {
//
//            case '0':
//
//                $result = 0;
//
//                break;
//
//            default:
//
//                $result = 31 - $num % 31;
//
//                break;
//
//        }
//
//        if (substr($str, -1, 1) == $this->transformation($result, true)) {
//            return true;
//
//        } else {
//            return false;
//        }
    }

    /**
     * @param $num
     * @param bool $status
     * @return mixed
     * @author 陈妙威
     */
    function transformation($num, $status = false)
    {

        $list = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17, 'J' => 18, 'K' => 19, 'L' => 20, 'M' => 21, 'N' => 22, 'P' => 23, 'Q' => 24, 'R' => 25, 'T' => 26, 'U' => 27, 'W' => 28, 'X' => 29, 'Y' => 30);//值转换

        if ($status == true) {

            $list = array_flip($list);

        }

        return $list[$num];

    }
}