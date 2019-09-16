<?php

namespace orangins\lib\export\field;

use orangins\lib\OranginsObject;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class PhabricatorExportField
 * @package orangins\lib\export\field
 * @author 陈妙威
 */
abstract class PhabricatorExportField
    extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $label;

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $value
     * @return null|string
     * @author 陈妙威
     */
    public function getTextValue($value)
    {
        $natural_value = $this->getNaturalValue($value);

        if ($natural_value === null) {
            return null;
        }

        return (string)$natural_value;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getNaturalValue($value)
    {
        return $value;
    }

    /**
     * @param $value
     * @return null|string
     * @author 陈妙威
     */
    public function getPHPExcelValue($value)
    {
        return $this->getTextValue($value);
    }

    /**
     * @phutil-external-symbol class PHPExcel_Cell_DataType
     * @param Worksheet $cell
     * @param $style
     */
    public function formatPHPExcelCell($cell, $style)
    {
//        $cell->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getCharacterWidth()
    {
        return 24;
    }

}
