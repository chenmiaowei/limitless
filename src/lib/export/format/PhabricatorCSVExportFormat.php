<?php

namespace orangins\lib\export\format;

/**
 * Class PhabricatorCSVExportFormat
 * @package orangins\lib\export\format
 * @author 陈妙威
 */
final class PhabricatorCSVExportFormat
    extends PhabricatorExportFormat
{

    /**
     *
     */
    const EXPORTKEY = 'csv';

    /**
     * @var array
     */
    private $rows = array();

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExportFormatName()
    {
        return \Yii::t("app", 'Comma-Separated Values (.csv)');
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
        return 'csv';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMIMEContentType()
    {
        return 'text/csv';
    }

    /**
     * @param array $fields
     * @author 陈妙威
     */
    public function addHeaders(array $fields)
    {
        $headers = mpull($fields, 'getLabel');
        $this->addRow($headers);
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
            $value = $field->getTextValue($value);
            $values[] = $value;
        }

        $this->addRow($values);
    }

    /**
     * @param array $values
     * @author 陈妙威
     */
    private function addRow(array $values)
    {
        $row = array();
        foreach ($values as $value) {

            // Excel is extremely interested in executing arbitrary code it finds in
            // untrusted CSV files downloaded from the internet. When a cell looks
            // like it might be too tempting for Excel to ignore, mangle the value
            // to dissuade remote code execution. See T12800.

            if (preg_match('/^\s*[+=@-]/', $value)) {
                $value = '(!) ' . $value;
            }

            if (preg_match('/\s|,|\"/', $value)) {
                $value = str_replace('"', '""', $value);
                $value = '"' . $value . '"';
            }

            $row[] = $value;
        }

        $this->rows[] = implode(',', $row);
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function newFileData()
    {
        return implode("\n", $this->rows);
    }

}
