<?php

namespace orangins\lib\export\format;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class PhabricatorExcelExportFormat
 * @package orangins\lib\export\format
 * @author 陈妙威
 */
final class PhabricatorExcelExportFormat
    extends PhabricatorExportFormat
{

    /**
     *
     */
    const EXPORTKEY = 'excel';

    /**
     * @var Spreadsheet
     */
    private $workbook;
    /**
     * @var Worksheet
     */
    private $sheet;
    /**
     * @var
     */
    private $rowCursor;

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExportFormatName()
    {
        return \Yii::t("app", 'Excel (.xlsx)');
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function isExportFormatEnabled()
    {
        // TODO: PHPExcel has a dependency on the PHP zip extension. We should test
        // for that here, since it fatals if we don't have the ZipArchive class.
        return class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getInstallInstructions()
    {
        return \Yii::t("app", <<<EOHELP
Data can not be exported to Excel because the PHPExcel library is not
installed. This software component is required for Phabricator to create
Excel files.

You can install PHPExcel from GitHub:

> https://phpspreadsheet.readthedocs.io/en/latest/

Briefly:

  - composer require phpoffice/phpspreadsheet
EOHELP
        );
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFileExtension()
    {
        return 'xlsx';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMIMEContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    /**
     * @phutil-external-symbol class PHPExcel_Cell_DataType
     * @param array $fields
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function addHeaders(array $fields)
    {
        $sheet = $this->getSheet();

        $header_format = array(
            'font' => array(
                'bold' => true,
            ),
        );

        $row = 1;
        $col = 1;
        foreach ($fields as $field) {
            $cell_value = $field->getLabel();

            $cell_name = $this->getCellName($col, $row);

            $cell = $sheet->setCellValue(
                $cell_name,
                $cell_value);

            $sheet->getStyle($cell_name)->applyFromArray($header_format);
//            $cell->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);

            $width = $field->getCharacterWidth();
            if ($width !== null) {
                $col_name = $this->getCellName($col);
                $sheet->getColumnDimension($col_name)
                    ->setWidth($width);
            }

            $col++;
        }
    }

    /**
     * @param $object
     * @param array $fields
     * @param array $map
     * @return mixed|void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @author 陈妙威
     */
    public function addObject($object, array $fields, array $map)
    {
        $sheet = $this->getSheet();

        $col = 1;
        foreach ($fields as $key => $field) {
            $cell_value = $map[$key];
            $cell_value = $field->getPHPExcelValue($cell_value);

            $cell_name = $this->getCellName($col, $this->rowCursor);

            $cell = $sheet->setCellValue(
                $cell_name,
                $cell_value);

            $style = $sheet->getStyle($cell_name);
            $field->formatPHPExcelCell($cell, $style);

            $col++;
        }

        $this->rowCursor++;
    }

    /**
     * @phutil-external-symbol class PHPExcel_IOFactory
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function newFileData()
    {
        $workbook = $this->getWorkbook();
        $writer = IOFactory::createWriter($workbook, 'Xlsx');

        ob_start();
        $writer->save('php://output');
        $data = ob_get_clean();

        return $data;
    }

    /**
     * @return Spreadsheet
     * @author 陈妙威
     */
    private function getWorkbook()
    {
        if (!$this->workbook) {
            $this->workbook = $this->newWorkbook();
        }
        return $this->workbook;
    }

    /**
     * @phutil-external-symbol class PHPExcel
     */
    private function newWorkbook()
    {
        $spreadsheet = new Spreadsheet();
        return $spreadsheet;
    }

    /**
     * @return Worksheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @author 陈妙威
     */
    private function getSheet()
    {
        if (!$this->sheet) {
            $workbook = $this->getWorkbook();

            $sheet = $workbook->setActiveSheetIndex(0);
            $sheet->setTitle($this->getTitle());

            $this->sheet = $sheet;

            // The row cursor starts on the second row, after the header row.
            $this->rowCursor = 2;
        }

        return $this->sheet;
    }


    /**
     * @phutil-external-symbol class PHPExcel_Cell
     * @param $col
     * @param null $row
     * @return string
     */
    private function getCellName($col, $row = null)
    {
        $col_name = Coordinate::stringFromColumnIndex($col);

        if ($row === null) {
            return $col_name;
        }

        return $col_name . $row;
    }
}
