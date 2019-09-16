<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 15/7/9
 * Time: 下午4:16
 */

namespace orangins\lib\helpers;


use http\Exception\UnexpectedValueException;

/**
 * Class StringHelper
 * @package Module\Kernel\Helper
 */
class OranginsStringHelper
{

    /**
     * Returns the number of bytes in the given string.
     * This method ensures the string is treated as a byte array by using `mb_strlen()`.
     * @param string $string the string being measured for length
     * @return integer the number of bytes in the given string.
     */
    public static function byteLength($string)
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     * This method ensures the string is treated as a byte array by using `mb_substr()`.
     * @param string $string the input string. Must be one character or longer.
     * @param integer $start the starting position
     * @param integer $length the desired portion length. If not specified or `null`, there will be
     * no limit on length i.e. the output will be until the end of the string.
     * @return string the extracted part of string, or FALSE on failure or an empty string.
     * @see http://www.php.net/manual/en/function.substr.php
     */
    public static function byteSubstr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
    }

    /**
     * Returns the trailing name component of a path.
     * This method is similar to the php function `basename()` except that it will
     * treat both \ and / as directory separators, independent of the operating system.
     * This method was mainly created to work on php namespaces. When working with real
     * file paths, php's `basename()` should work fine for you.
     * Note: this method is not aware of the actual filesystem, or path components such as "..".
     *
     * @param string $path A path string.
     * @param string $suffix If the name component ends in suffix this will also be cut off.
     * @return string the trailing name component of the given path.
     * @see http://www.php.net/manual/en/function.basename.php
     */
    public static function basename($path, $suffix = '')
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) == $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }

    /**
     * Returns parent directory's path.
     * This method is similar to `dirname()` except that it will treat
     * both \ and / as directory separators, independent of the operating system.
     *
     * @param string $path A path string.
     * @return string the parent directory's path.
     * @see http://www.php.net/manual/en/function.basename.php
     */
    public static function dirname($path)
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');
        if ($pos !== false) {
            return mb_substr($path, 0, $pos);
        } else {
            return '';
        }
    }

    /**
     * Truncates a string to the number of characters specified.
     *
     * @param string $string The string to truncate.
     * @param integer $length How many characters from original string to include into truncated string.
     * @param string $suffix String to append to the end of truncated string.
     * @param string $encoding The charset to use, defaults to charset currently used by application.
     * @return string the truncated string.
     */
    public static function truncate($string, $length, $suffix = '...', $encoding = null)
    {
        if (mb_strlen($string, $encoding ?: "utf-8") > $length) {
            return trim(mb_substr($string, 0, $length, $encoding ?: "utf-8")) . $suffix;
        } else {
            return $string;
        }
    }

    /**
     * Truncates a string to the number of words specified.
     *
     * @param string $string The string to truncate.
     * @param integer $count How many words from original string to include into truncated string.
     * @param string $suffix String to append to the end of truncated string.
     * @return string the truncated string.
     */
    public static function truncateWords($string, $count, $suffix = '...')
    {
        $words = preg_split('/(\s+)/u', trim($string), null, PREG_SPLIT_DELIM_CAPTURE);
        if (count($words) / 2 > $count) {
            return implode('', array_slice($words, 0, ($count * 2) - 1)) . $suffix;
        } else {
            return $string;
        }
    }


    /**
     * @param $plateNo
     * @return string
     * @throws UnexpectedValueException
     */
    public static function getPlateNo($plateNo)
    {
        if (preg_match("/^[京津沪渝冀豫云辽黑湘皖鲁苏赣浙粤鄂桂甘晋蒙陕吉闽贵青藏川宁新琼]{1}[a-zA-Z]{1}[a-zA-Z0-9]{5,6}$/u", $plateNo, $match)) {
            return mb_substr($plateNo, 0, 1, "utf-8") . strtoupper(mb_substr($plateNo, 1, mb_strlen($plateNo), "utf-8"));
        } else {
            throw new UnexpectedValueException("请检查车牌号是否正确");
        }
    }

    /**
     * 获取到随机的名字
     * @return string
     */
    public static function getRandName()
    {
        $randomChinaName = new RandomChinaName();
        return $randomChinaName->getName(2);
    }

    /**
     * @param $string
     * @return string
     */
    public static function trimArray($string)
    {
        $a = explode(",", $string);
        $result = [];
        foreach ($a as $v) {
            if (!ValidatorHelper::isEmpty($v)) {
                $result[] = $v;
            }
        }
        return implode(",", $result);
    }

    /**
     * @param $scored
     * Transforms an under_scored_string to a camelCasedOne
     * @return string
     */
    public static function camelize($scored)
    {
        return lcfirst(
            implode(
                '',
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode(
                            '_', $scored)))));
    }

    /**
     * @param $cameled
     * Transforms a camelCasedString to an under_scored_one
     * @return string
     */
    public static function underscore($cameled)
    {
        return implode(
            '_',
            array_map(
                'strtolower',
                preg_split('/([A-Z]{1}[^A-Z]*)/', $cameled, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)));
    }


    /**
     * Translates a number to a short alhanumeric version
     *
     * Translated any number up to 9007199254740992
     * to a shorter version in letters e.g.:
     * 9007199254740989 --> PpQXn7COf
     *
     * specifiying the second argument true, it will
     * translate back e.g.:
     * PpQXn7COf --> 9007199254740989
     *
     * this function is based on any2dec && dec2any by
     * fragmer[at]mail[dot]ru
     * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
     *
     * If you want the alphaID to be at least 3 letter long, use the
     * $pad_up = 3 argument
     *
     * In most cases this is better than totally random ID generators
     * because this can easily avoid duplicate ID's.
     * For example if you correlate the alpha ID to an auto incrementing ID
     * in your database, you're done.
     *
     * The reverse is done because it makes it slightly more cryptic,
     * but it also makes it easier to spread lots of IDs in different
     * directories on your filesystem. Example:
     * $part1 = substr($alpha_id,0,1);
     * $part2 = substr($alpha_id,1,1);
     * $part3 = substr($alpha_id,2,strlen($alpha_id));
     * $destindir = "/".$part1."/".$part2."/".$part3;
     * // by reversing, directories are more evenly spread out. The
     * // first 26 directories already occupy 26 main levels
     *
     * more info on limitation:
     * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
     *
     * if you really need this for bigger numbers you probably have to look
     * at things like: http://theserverpages.com/php/manual/en/ref.bc.php
     * or: http://theserverpages.com/php/manual/en/ref.gmp.php
     * but I haven't really dugg into this. If you have more info on those
     * matters feel free to leave a comment.
     *
     * The following code block can be utilized by PEAR's Testing_DocTest
     * <code>
     * // Input //
     * $number_in = 2188847690240;
     * $alpha_in  = "SpQXn7Cb";
     *
     * // Execute //
     * $alpha_out  = alphaID($number_in, false, 8);
     * $number_out = alphaID($alpha_in, true, 8);
     *
     * if ($number_in != $number_out) {
     *   echo "Conversion failure, ".$alpha_in." returns ".$number_out." instead of the ";
     *   echo "desired: ".$number_in."\n";
     * }
     * if ($alpha_in != $alpha_out) {
     *   echo "Conversion failure, ".$number_in." returns ".$alpha_out." instead of the ";
     *   echo "desired: ".$alpha_in."\n";
     * }
     *
     * // Show //
     * echo $number_out." => ".$alpha_out."\n";
     * echo $alpha_in." => ".$number_out."\n";
     * echo alphaID(238328, false)." => ".alphaID(alphaID(238328, false), true)."\n";
     *
     * // expects:
     * // 2188847690240 => SpQXn7Cb
     * // SpQXn7Cb => 2188847690240
     * // aaab => 238328
     *
     * </code>
     *
     * @author  Kevin van Zonneveld &lt;kevin@vanzonneveld.net>
     * @author  Simon Franz
     * @author  Deadfish
     * @author  SK83RJOSH
     * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
     * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
     * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
     * @link    http://kevin.vanzonneveld.net/
     *
     * @param mixed $in String or long input to translate
     * @param boolean $to_num Reverses translation when true
     * @param mixed $pad_up Number or boolean padds the result up to a specified length
     * @param string $pass_key Supplying a password makes it harder to calculate the original ID
     *
     * @return mixed string or long
     */
    public static function alphaID($in, $to_num = false, $pad_up = false, $pass_key = null)
    {
        $out = '';
        $index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($index);

        if ($pass_key !== null) {
            // Although this function's purpose is to just make the
            // ID short - and not so much secure,
            // with this patch by Simon Franz (http://blog.snaky.org/)
            // you can optionally supply a password to make it harder
            // to calculate the corresponding numeric ID

            for ($n = 0; $n < strlen($index); $n++) {
                $i[] = substr($index, $n, 1);
            }

            $pass_hash = hash('sha256', $pass_key);
            $pass_hash = (strlen($pass_hash) < strlen($index) ? hash('sha512', $pass_key) : $pass_hash);

            for ($n = 0; $n < strlen($index); $n++) {
                $p[] = substr($pass_hash, $n, 1);
            }

            array_multisort($p, SORT_DESC, $i);
            $index = implode($i);
        }

        if ($to_num) {
            // Digital number  <<--  alphabet letter code
            $len = strlen($in) - 1;

            for ($t = $len; $t >= 0; $t--) {
                $bcp = bcpow($base, $len - $t);
                $out = $out + strpos($index, substr($in, $t, 1)) * $bcp;
            }

            if (is_numeric($pad_up)) {
                $pad_up--;

                if ($pad_up > 0) {
                    $out -= pow($base, $pad_up);
                }
            }
        } else {
            // Digital number  -->>  alphabet letter code
            if (is_numeric($pad_up)) {
                $pad_up--;

                if ($pad_up > 0) {
                    $in += pow($base, $pad_up);
                }
            }

            for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; $t--) {
                $bcp = bcpow($base, $t);
                $a = floor($in / $bcp) % $base;
                $out = $out . substr($index, $a, 1);
                $in = $in - ($a * $bcp);
            }
        }

        return $out;
    }

    /**
     * @param $string
     * @param $value
     * @return string
     */
    public static function insertValueToString($string, $value)
    {
        if ($string === null || $string === "") {
            $array = [];
        } else {
            $array = explode(',', $string);
        }

        foreach ($array as $k => $item) {
            if ($item === "") {
                unset($array[$k]);
            }
        }
        array_push($array, $value);
        return implode(",", $array);
    }

    /**
     * @param $action
     * @return string
     */
    public static function parseAction($action)
    {
        return 'action' . ucfirst(OranginsStringHelper::camelize(str_replace("-", "_", $action)));
    }

    /**
     * 判断文字是否繁体
     * @param $plateNo
     * @return bool
     */
    public static function isFanti($plateNo)
    {
        if (preg_match("/^[滬雲遼魯蘇贛粵晉陝閩貴甯瓊]{1}[a-zA-Z]{1}[a-zA-Z0-9]{5}$/u", $plateNo, $match)) {
            return true;
        } else {
            return false;
        }
    }

    public static function getRandMobile()
    {
        return "158" . rand(12345678, 87654321);
    }
}