<?php

namespace orangins\modules\dashboard\layoutconfig;

use orangins\lib\OranginsObject;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardLayoutConfig
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardLayoutConfig extends OranginsObject
{

    /**
     *
     */
    const MODE_FULL = 'layout-mode-full';
    /**
     *
     */
    const MODE_HALF_AND_HALF = 'layout-mode-half-and-half';
    /**
     *
     */
    const MODE_THIRD_AND_THIRDS = 'layout-mode-third-and-thirds';
    /**
     *
     */
    const MODE_THIRDS_AND_THIRD = 'layout-mode-thirds-and-third';

    /**
     * @var string
     */
    private $layoutMode = self::MODE_FULL;
    /**
     * @var array
     */
    private $panelLocations = array();

    /**
     * @param $mode
     * @return $this
     * @author 陈妙威
     */
    public function setLayoutMode($mode)
    {
        $this->layoutMode = $mode;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLayoutMode()
    {
        return $this->layoutMode;
    }

    /**
     * @param $which_column
     * @param $panel_phid
     * @return $this
     * @author 陈妙威
     */
    public function setPanelLocation($which_column, $panel_phid)
    {
        $this->panelLocations[$which_column][] = $panel_phid;
        return $this;
    }

    /**
     * @param array $locations
     * @return $this
     * @author 陈妙威
     */
    public function setPanelLocations(array $locations)
    {
        $this->panelLocations = $locations;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPanelLocations()
    {
        return $this->panelLocations;
    }

    /**
     * @param $old_phid
     * @param $new_phid
     * @return PhabricatorDashboardLayoutConfig
     * @author 陈妙威
     */
    public function replacePanel($old_phid, $new_phid)
    {
        $locations = $this->getPanelLocations();
        foreach ($locations as $column => $panel_phids) {
            foreach ($panel_phids as $key => $panel_phid) {
                if ($panel_phid == $old_phid) {
                    $locations[$column][$key] = $new_phid;
                }
            }
        }
        return $this->setPanelLocations($locations);
    }

    /**
     * @param $panel_phid
     * @author 陈妙威
     */
    public function removePanel($panel_phid)
    {
        $panel_location_grid = $this->getPanelLocations();
        foreach ($panel_location_grid as $column => $panel_columns) {
            $found_old_column = array_search($panel_phid, $panel_columns);
            if ($found_old_column !== false) {
                $new_panel_columns = $panel_columns;
                array_splice(
                    $new_panel_columns,
                    $found_old_column,
                    1,
                    array());
                $panel_location_grid[$column] = $new_panel_columns;
                break;
            }
        }
        $this->setPanelLocations($panel_location_grid);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDefaultPanelLocations()
    {
        switch ($this->getLayoutMode()) {
            case self::MODE_HALF_AND_HALF:
            case self::MODE_THIRD_AND_THIRDS:
            case self::MODE_THIRDS_AND_THIRD:
                $locations = array(array(), array());
                break;
            case self::MODE_FULL:
            default:
                $locations = array(array());
                break;
        }
        return $locations;
    }

    /**
     * @param $column_index
     * @param bool $grippable
     * @return null|string
     * @author 陈妙威
     */
    public function getColumnClass($column_index, $grippable = false)
    {
        switch ($this->getLayoutMode()) {
            case self::MODE_HALF_AND_HALF:
                $class = 'col-lg-6';
                break;
            case self::MODE_THIRD_AND_THIRDS:
                if ($column_index) {
                    $class = 'col-lg-8';
                } else {
                    $class = 'col-lg-4';
                }
                break;
            case self::MODE_THIRDS_AND_THIRD:
                if ($column_index) {
                    $class = 'col-lg-4';
                } else {
                    $class = 'col-lg-8';
                }
                break;
            case self::MODE_FULL:
            default:
                $class = 'col-lg-12';
                break;
        }
        if ($grippable) {
            $class .= ' grippable';
        }
        return $class;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isMultiColumnLayout()
    {
        return $this->getLayoutMode() != self::MODE_FULL;
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public function getColumnSelectOptions()
    {
        $options = array();

        switch ($this->getLayoutMode()) {
            case self::MODE_HALF_AND_HALF:
            case self::MODE_THIRD_AND_THIRDS:
            case self::MODE_THIRDS_AND_THIRD:
                return array(
                    0 => \Yii::t("app", 'Left'),
                    1 => \Yii::t("app", 'Right'),
                );
                break;
            case self::MODE_FULL:
                throw new Exception(\Yii::t("app", 'There is only one column in mode full.'));
                break;
            default:
                throw new Exception(\Yii::t("app", 'Unknown layout mode!'));
                break;
        }
        return $options;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getLayoutModeSelectOptions()
    {
        return array(
            self::MODE_FULL => \Yii::t("app", 'One full-width column'),
            self::MODE_HALF_AND_HALF => \Yii::t("app", 'Two columns, 1/2 and 1/2'),
            self::MODE_THIRD_AND_THIRDS => \Yii::t("app", 'Two columns, 1/3 and 2/3'),
            self::MODE_THIRDS_AND_THIRD => \Yii::t("app", 'Two columns, 2/3 and 1/3'),
        );
    }

    /**
     * @param array $dict
     * @return mixed
     * @author 陈妙威
     */
    public static function newFromDictionary(array $dict)
    {
        $layout_config = (new PhabricatorDashboardLayoutConfig())
            ->setLayoutMode(ArrayHelper::getValue($dict, 'layoutMode', self::MODE_FULL));
        $layout_config->setPanelLocations(ArrayHelper::getValue(
            $dict,
            'panelLocations',
            $layout_config->getDefaultPanelLocations()));

        return $layout_config;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function toDictionary()
    {
        return array(
            'layoutMode' => $this->getLayoutMode(),
            'panelLocations' => $this->getPanelLocations(),
        );
    }

}
