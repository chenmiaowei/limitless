<?php

namespace orangins\lib\export\format;

use PhutilJSON;

/**
 * Class PhabricatorJSONExportFormat
 * @package orangins\lib\export\format
 * @author 陈妙威
 */
final class PhabricatorJSONExportFormat
    extends PhabricatorExportFormat
{

    /**
     *
     */
    const EXPORTKEY = 'json';

    /**
     * @var array
     */
    private $objects = array();

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExportFormatName()
    {
        return 'JSON (.json)';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isExportFormatEnabled()
    {
        return true;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFileExtension()
    {
        return 'json';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMIMEContentType()
    {
        return 'application/json';
    }

    /**
     * @param $object
     * @param array $fields
     * @param array $map
     * @return mixed|void
     * @author 陈妙威
     */
    public function addObject($object, array $fields, array $map)
    {
        $values = array();
        foreach ($fields as $key => $field) {
            $value = $map[$key];
            $value = $field->getNaturalValue($value);

            $values[$key] = $value;
        }

        $this->objects[] = $values;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function newFileData()
    {
        return (new PhutilJSON())
            ->encodeAsList($this->objects);
    }

}
