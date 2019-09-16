<?php

/**
 * Represent and manipulate IPv4 addresses.
 */
final class PhutilIPv4Address extends PhutilIPAddress
{

    /**
     * @var
     */
    private $ip;
    /**
     * @var
     */
    private $bits;

    /**
     * PhutilIPv4Address constructor.
     */
    private function __construct()
    {
        // <private>
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAddress()
    {
        return $this->ip;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getBitCount()
    {
        return 32;
    }

    /**
     * @param $str
     * @return PhutilIPv4Address
     * @throws Exception
     * @author 陈妙威
     */
    public static function newFromString($str)
    {
        $matches = null;
        $ok = preg_match('(^(\d+)\.(\d+)\.(\d+).(\d+)\z)', $str, $matches);
        if (!$ok) {
            throw new Exception(
                pht(
                    'IP address "%s" is not properly formatted. Expected an IPv4 ' .
                    'address like "%s".',
                    $str,
                    '23.45.67.89'));
        }

        $parts = array_slice($matches, 1);
        foreach ($parts as $part) {
            if (preg_match('/^0\d/', $part)) {
                throw new Exception(
                    pht(
                        'IP address "%s" is not properly formatted. Address segments ' .
                        'should have no leading zeroes, but segment "%s" has a leading ' .
                        'zero.',
                        $str,
                        $part));
            }

            $value = (int)$part;
            if ($value < 0 || $value > 255) {
                throw new Exception(
                    pht(
                        'IP address "%s" is not properly formatted. Address segments ' .
                        'should be between 0 and 255, inclusive, but segment "%s" has ' .
                        'a value outside of this range.',
                        $str,
                        $part));
            }
        }

        $obj = new self();
        $obj->ip = $str;

        return $obj;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function toBits()
    {
        if ($this->bits === null) {
            $bits = '';
            foreach (explode('.', $this->ip) as $part) {
                $value = (int)$part;
                for ($ii = 7; $ii >= 0; $ii--) {
                    $mask = (1 << $ii);
                    if (($value & $mask) === $mask) {
                        $bits .= '1';
                    } else {
                        $bits .= '0';
                    }
                }
            }

            $this->bits = $bits;
        }

        return $this->bits;
    }

}
