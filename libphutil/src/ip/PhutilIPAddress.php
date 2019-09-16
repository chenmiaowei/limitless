<?php

/**
 * Represent and manipulate IPv4 and IPv6 addresses.
 */
abstract class PhutilIPAddress
    extends Phobject
{

    /**
     * PhutilIPAddress constructor.
     */
    private function __construct()
    {
        // <private>
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function toBits();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getBitCount();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAddress();

    /**
     * @param $in
     * @return PhutilIPAddress|PhutilIPv4Address|PhutilIPv6Address
     * @throws Exception
     * @author 陈妙威
     */
    public static function newAddress($in)
    {
        if ($in instanceof PhutilIPAddress) {
            return clone $in;
        }

        try {
            return PhutilIPv4Address::newFromString($in);
        } catch (Exception $ex) {
            // Continue, trying the address as IPv6 instead.
        }

        try {
            return PhutilIPv6Address::newFromString($in);
        } catch (Exception $ex) {
            // Continue, throwing a more tailored exception below.
        }

        throw new Exception(
            pht(
                'IP address "%s" is not properly formatted. Expected an IPv4 address ' .
                'like "%s", or an IPv6 address like "%s".',
                $in,
                '23.45.67.89',
                '2345:6789:0123:abcd::'));
    }

}
