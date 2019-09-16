<?php

namespace orangins\modules\dashboard\layoutconfig;

use Filesystem;
use orangins\lib\OranginsObject;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardPanelRefList
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelRefList
    extends OranginsObject
{

    /**
     * @var
     */
    private $refs;
    /**
     * @var
     */
    private $columns;

    /**
     * @param $config
     * @return PhabricatorDashboardPanelRefList
     * @author 陈妙威
     */
    public static function newFromDictionary($config)
    {
        if (!is_array($config)) {
            $config = array();
        }

        $mode_map = PhabricatorDashboardLayoutMode::getAllLayoutModes();
        $mode_key = ArrayHelper::getValue($config, 'layoutMode');
        if (!isset($mode_map[$mode_key])) {
            $mode_key = head_key($mode_map);
        }
        /** @var PhabricatorDashboardLayoutMode $mode */
        $mode = $mode_map[$mode_key];

        $columns = $mode->getLayoutModeColumns();
        $columns = mpull($columns, null, 'getColumnKey');
        $default_column = head($columns);

        $panels = ArrayHelper::getValue($config, 'panels');
        if (!is_array($panels)) {
            $panels = array();
        }

        $seen_panels = array();
        $refs = array();
        foreach ($panels as $panel) {
            $panel_phid = ArrayHelper::getValue($panel, 'panelPHID');
            if (!strlen($panel_phid)) {
                continue;
            }

            $panel_key = ArrayHelper::getValue($panel, 'panelKey');
            if (!strlen($panel_key)) {
                continue;
            }

            if (isset($seen_panels[$panel_key])) {
                continue;
            }
            $seen_panels[$panel_key] = true;

            $column_key = ArrayHelper::getValue($panel, 'columnKey');
            $column = ArrayHelper::getValue($columns, $column_key, $default_column);

            $ref = (new PhabricatorDashboardPanelRef())
                ->setPanelPHID($panel_phid)
                ->setPanelKey($panel_key)
                ->setColumnKey($column->getColumnKey());

            $column->addPanelRef($ref);
            $refs[] = $ref;
        }

        $list = new self();

        $list->columns = $columns;
        $list->refs = $refs;

        return $list;
    }

    /**
     * @return PhabricatorDashboardColumn[]
     * @author 陈妙威
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return PhabricatorDashboardPanelRef[]
     * @author 陈妙威
     */
    public function getPanelRefs()
    {
        return $this->refs;
    }

    /**
     * @param $panel_key
     * @return null
     * @author 陈妙威
     */
    public function getPanelRef($panel_key)
    {
        foreach ($this->getPanelRefs() as $ref) {
            if ($ref->getPanelKey() === $panel_key) {
                return $ref;
            }
        }

        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function toDictionary()
    {
        $phabricatorDashboardPanelRefs = $this->getPanelRefs();
        return array_values(mpull($phabricatorDashboardPanelRefs, 'toDictionary'));
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @param $column_key
     * @return mixed
     * @author 陈妙威
     */
    public function newPanelRef(PhabricatorDashboardPanel $panel, $column_key)
    {
        $ref = (new PhabricatorDashboardPanelRef())
            ->setPanelKey($this->newPanelKey())
            ->setPanelPHID($panel->getPHID())
            ->setColumnKey($column_key);

        $this->refs[] = $ref;

        return $ref;
    }

    /**
     * @param PhabricatorDashboardPanelRef $target
     * @return null
     * @author 陈妙威
     */
    public function removePanelRef(PhabricatorDashboardPanelRef $target)
    {
        foreach ($this->refs as $key => $ref) {
            if ($ref->getPanelKey() !== $target->getPanelKey()) {
                continue;
            }

            unset($this->refs[$key]);
            return $ref;
        }

        return null;
    }

    /**
     * @param PhabricatorDashboardPanelRef $target
     * @param $column_key
     * @param PhabricatorDashboardPanelRef|null $after
     * @return mixed
     * @author 陈妙威
     */
    public function movePanelRef(
        PhabricatorDashboardPanelRef $target,
        $column_key,
        PhabricatorDashboardPanelRef $after = null)
    {

        $target->setColumnKey($column_key);

        $results = array();

        if (!$after) {
            $results[] = $target;
        }

        foreach ($this->refs as $ref) {
            if ($ref->getPanelKey() === $target->getPanelKey()) {
                continue;
            }

            $results[] = $ref;

            if ($after) {
                if ($ref->getPanelKey() === $after->getPanelKey()) {
                    $results[] = $target;
                }
            }
        }

        $this->refs = $results;

        $column_map = mgroup($results, 'getColumnKey');
        foreach ($this->columns as $column_key => $column) {
            $column->setPanelRefs(ArrayHelper::getValue($column_map, $column_key, array()));
        }

        return $ref;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newPanelKey()
    {
        return Filesystem::readRandomCharacters(8);
    }
}
