<?php

namespace orangins\lib\view\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use PhutilURI;
use orangins\lib\view\AphrontView;
use yii\helpers\ArrayHelper;

/**
 * Class AphrontTableView
 * @package orangins\lib\view\control
 * @author 陈妙威
 */
final class AphrontTableView extends AphrontView
{
    /**
     * @var array
     */
    protected $data;
    /**
     * @var
     */
    protected $headers;
    /**
     * @var array
     */
    protected $shortHeaders = array();
    /**
     * @var array
     */
    protected $rowClasses = array();
    /**
     * @var array
     */
    protected $rowAttributes = [];
    /**
     * @var array
     */
    protected $columnClasses = array();
    /**
     * @var array
     */
    protected $cellClasses = array();
    /**
     * @var bool
     */
    protected $zebraStripes = true;
    /**
     * @var
     */
    protected $noDataString;
    /**
     * @var
     */
    protected $className;
    /**
     * @var
     */
    protected $notice;
    /**
     * @var array
     */
    protected $columnVisibility = array();
    /**
     * @var array
     */
    private $deviceVisibility = array();

    /**
     * @var array
     */
    private $columnWidths = array();

    /**
     * @var
     */
    protected $sortURI;
    /**
     * @var
     */
    protected $sortParam;
    /**
     * @var
     */
    protected $sortSelected;
    /**
     * @var
     */
    protected $sortReverse;
    /**
     * @var array
     */
    protected $sortValues = array();
    /**
     * @var
     */
    private $deviceReadyTable;

    /**
     * AphrontTableView constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array $headers
     * @return $this
     * @author 陈妙威
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param array $column_classes
     * @return $this
     * @author 陈妙威
     */
    public function setColumnClasses(array $column_classes)
    {
        $this->columnClasses = $column_classes;
        return $this;
    }

    /**
     * @param array $rowAttributes
     * @return self
     */
    public function setRowAttributes($rowAttributes)
    {
        $this->rowAttributes = $rowAttributes;
        return $this;
    }

    /**
     * @param array $row_classes
     * @return $this
     * @author 陈妙威
     */
    public function setRowClasses(array $row_classes)
    {
        $this->rowClasses = $row_classes;
        return $this;
    }

    /**
     * @param array $cell_classes
     * @return $this
     * @author 陈妙威
     */
    public function setCellClasses(array $cell_classes)
    {
        $this->cellClasses = $cell_classes;
        return $this;
    }

    /**
     * @param array $widths
     * @return $this
     * @author 陈妙威
     */
    public function setColumnWidths(array $widths)
    {
        $this->columnWidths = $widths;
        return $this;
    }

    /**
     * @param $no_data_string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data_string)
    {
        $this->noDataString = $no_data_string;
        return $this;
    }

    /**
     * @param $class_name
     * @return $this
     * @author 陈妙威
     */
    public function setClassName($class_name)
    {
        $this->className = $class_name;
        return $this;
    }

    /**
     * @param $notice
     * @return $this
     * @author 陈妙威
     */
    public function setNotice($notice)
    {
        $this->notice = $notice;
        return $this;
    }

    /**
     * @param $zebra_stripes
     * @return $this
     * @author 陈妙威
     */
    public function setZebraStripes($zebra_stripes)
    {
        $this->zebraStripes = $zebra_stripes;
        return $this;
    }

    /**
     * @param array $visibility
     * @return $this
     * @author 陈妙威
     */
    public function setColumnVisibility(array $visibility)
    {
        $this->columnVisibility = $visibility;
        return $this;
    }

    /**
     * @param array $device_visibility
     * @return $this
     * @author 陈妙威
     */
    public function setDeviceVisibility(array $device_visibility)
    {
        $this->deviceVisibility = $device_visibility;
        return $this;
    }

    /**
     * @param $ready
     * @return $this
     * @author 陈妙威
     */
    public function setDeviceReadyTable($ready)
    {
        $this->deviceReadyTable = $ready;
        return $this;
    }

    /**
     * @param array $short_headers
     * @return $this
     * @author 陈妙威
     */
    public function setShortHeaders(array $short_headers)
    {
        $this->shortHeaders = $short_headers;
        return $this;
    }

    /**
     * Parse a sorting parameter:
     *
     *   list($sort, $reverse) = AphrontTableView::parseSortParam($sort_param);
     *
     * @param string  Sort request parameter.
     * @return array Sort value, sort direction.
     */
    public static function parseSort($sort)
    {
        return array(ltrim($sort, '-'), preg_match('/^-/', $sort));
    }

    /**
     * @param PhutilURI $base_uri
     * @param $param
     * @param $selected
     * @param $reverse
     * @param array $sort_values
     * @return $this
     * @author 陈妙威
     */
    public function makeSortable(
        PhutilURI $base_uri,
        $param,
        $selected,
        $reverse,
        array $sort_values)
    {

        $this->sortURI = $base_uri;
        $this->sortParam = $param;
        $this->sortSelected = $selected;
        $this->sortReverse = $reverse;
        $this->sortValues = array_values($sort_values);

        return $this;
    }

    /**
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
//        require_celerity_resource('aphront-table-view-css');
        $table = array();

        $col_classes = array();
        foreach ($this->columnClasses as $key => $class) {
            if (strlen($class)) {
                $col_classes[] = $class;
            } else {
                $col_classes[] = null;
            }
        }

        $visibility = array_values($this->columnVisibility);
        $device_visibility = array_values($this->deviceVisibility);

        $column_widths = $this->columnWidths;

        $headers = $this->headers;
        $short_headers = $this->shortHeaders;
        $sort_values = $this->sortValues;
        if ($headers) {
            while (count($headers) > count($visibility)) {
                $visibility[] = true;
            }
            while (count($headers) > count($device_visibility)) {
                $device_visibility[] = true;
            }
            while (count($headers) > count($short_headers)) {
                $short_headers[] = null;
            }
            while (count($headers) > count($sort_values)) {
                $sort_values[] = null;
            }

            $tr = array();
            foreach ($headers as $col_num => $header) {
                if (!$visibility[$col_num]) {
                    continue;
                }

                $classes = array();

                if (!empty($col_classes[$col_num])) {
                    $classes[] = $col_classes[$col_num];
                }

                if (empty($device_visibility[$col_num])) {
                    $classes[] = 'aphront-table-view-nodevice';
                }

                if ($sort_values[$col_num] !== null) {
                    $classes[] = 'aphront-table-view-sortable';

                    $sort_value = $sort_values[$col_num];
                    $sort_glyph_class = 'aphront-table-down-sort';
                    if ($sort_value == $this->sortSelected) {
                        if ($this->sortReverse) {
                            $sort_glyph_class = 'aphront-table-up-sort';
                        } else if (!$this->sortReverse) {
                            $sort_value = '-' . $sort_value;
                        }
                        $classes[] = 'aphront-table-view-sortable-selected';
                    }

                    $sort_glyph = JavelinHtml::phutil_tag(
                        'span',
                        array(
                            'class' => $sort_glyph_class,
                        ),
                        '');

                    $header = JavelinHtml::phutil_tag(
                        'a',
                        array(
                            'href' => $this->sortURI->alter($this->sortParam, $sort_value),
                            'class' => 'aphront-table-view-sort-link',
                        ),
                        array(
                            $header,
                            ' ',
                            $sort_glyph,
                        ));
                }

                if ($classes) {
                    $class = implode(' ', $classes);
                } else {
                    $class = null;
                }

                if ($short_headers[$col_num] !== null) {
                    $header_nodevice = JavelinHtml::phutil_tag(
                        'span',
                        array(
                            'class' => 'aphront-table-view-nodevice',
                        ),
                        $header);
                    $header_device = JavelinHtml::phutil_tag(
                        'span',
                        array(
                            'class' => 'aphront-table-view-device',
                        ),
                        $short_headers[$col_num]);

                    $header = JavelinHtml::hsprintf('%s %s', $header_nodevice, $header_device);
                }

                $style = null;
                if (isset($column_widths[$col_num])) {
                    $style = 'width: ' . $column_widths[$col_num] . ';';
                }

                $tr[] = JavelinHtml::phutil_tag(
                    'th',
                    array(
                        'class' => $class,
                        'style' => $style,
                    ),
                    $header);
            }
            $table[] = JavelinHtml::phutil_tag('tr', array(), $tr);
        }

        foreach ($col_classes as $key => $value) {

            if (isset($sort_values[$key]) &&
                ($sort_values[$key] == $this->sortSelected)) {
                $value = trim($value . ' sorted-column');
            }

            if ($value !== null) {
                $col_classes[$key] = $value;
            }
        }

        $data = $this->data;
        if ($data) {
            $row_num = 0;
            foreach ($data as $row) {
                $row_size = count($row);
                while (count($row) > count($col_classes)) {
                    $col_classes[] = null;
                }
                while (count($row) > count($visibility)) {
                    $visibility[] = true;
                }
                while (count($row) > count($device_visibility)) {
                    $device_visibility[] = true;
                }
                $tr = array();
                // NOTE: Use of a separate column counter is to allow this to work
                // correctly if the row data has string or non-sequential keys.
                $col_num = 0;
                foreach ($row as $value) {
                    if (!$visibility[$col_num]) {
                        ++$col_num;
                        continue;
                    }
                    $class = $col_classes[$col_num];
                    if (empty($device_visibility[$col_num])) {
                        $class = trim($class . ' aphront-table-view-nodevice');
                    }
                    if (!empty($this->cellClasses[$row_num][$col_num])) {
                        $class = trim($class . ' ' . $this->cellClasses[$row_num][$col_num]);
                    }

                    $tr[] = JavelinHtml::phutil_tag(
                        'td',
                        array(
                            'class' => $class,
                        ),
                        $value);
                    ++$col_num;
                }

                $class = ArrayHelper::getValue($this->rowClasses, $row_num);
                if ($this->zebraStripes && ($row_num % 2)) {
                    if ($class !== null) {
                        $class = 'alt alt-' . $class;
                    } else {
                        $class = 'alt';
                    }
                }
                $rowAttributes = ArrayHelper::getValue($this->rowAttributes, $row_num, []);
                $table[] = JavelinHtml::phutil_tag('tr', ArrayHelper::merge(array('class' => $class), $rowAttributes), $tr);
                ++$row_num;
            }
        } else {
            $colspan = max(count(array_filter($visibility)), 1);
            $table[] = JavelinHtml::phutil_tag(
                'tr',
                array('class' => 'no-data'),
                JavelinHtml::phutil_tag(
                    'td',
                    array('colspan' => $colspan),
                    coalesce($this->noDataString, \Yii::t("app", 'No data available.'))));
        }

        $classes = array();
        $classes[] = 'table aphront-table-view';
        if ($this->className !== null) {
            $classes[] = $this->className;
        }

        if ($this->deviceReadyTable) {
            $classes[] = 'aphront-table-view-device-ready';
        }

        if ($this->columnWidths) {
            $classes[] = 'aphront-table-view-fixed';
        }

        $notice = null;
        if ($this->notice) {
            $notice = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'aphront-table-notice',
                ),
                $this->notice);
        }

        $html = JavelinHtml::phutil_tag(
            'table',
            array(
                'class' => implode(' ', $classes),
            ),
            $table);

        return JavelinHtml::phutil_tag_div(
            'table-responsive aphront-table-wrap',
            array(
                $notice,
                $html,
            ));
    }

    /**
     * @param $line
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public static function renderSingleDisplayLine($line)
    {

        // TODO: Is there a cleaner way to do this? We use a relative div with
        // overflow hidden to provide the bounds, and an absolute span with
        // white-space: pre to prevent wrapping. We need to append a character
        // (&nbsp; -- nonbreaking space) afterward to give the bounds div height
        // (alternatively, we could hard-code the line height). This is gross but
        // it's not clear that there's a better appraoch.

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'single-display-line-bounds',
            ),
            array(
                JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => 'single-display-line-content',
                    ),
                    $line),
                "\xC2\xA0",
            ));
    }


}
