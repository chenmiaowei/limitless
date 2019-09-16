<?php

namespace orangins\lib\infrastructure\query\policy;

use Exception;
use orangins\lib\OranginsObject;

/**
 * Class PhabricatorQueryCursor
 * @package orangins\lib\infrastructure\query\policy
 * @author 陈妙威
 */
final class PhabricatorQueryCursor
    extends OranginsObject
{

    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $rawRow;

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param array $raw_row
     * @return $this
     * @author 陈妙威
     */
    public function setRawRow($raw_row)
    {
        $this->rawRow = $raw_row;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRawRow()
    {
        return $this->rawRow;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function getRawRowProperty($key)
    {
        if (!is_array($this->rawRow)) {
            throw new Exception(
                pht(
                    'Caller is trying to "getRawRowProperty()" with key "%s", but this ' .
                    'cursor has no raw row.',
                    $key));
        }

        if (!array_key_exists($key, $this->rawRow)) {
            throw new Exception(
                pht(
                    'Caller is trying to access raw row property "%s", but the row ' .
                    'does not have this property.',
                    $key));
        }

        return $this->rawRow[$key];
    }

}
