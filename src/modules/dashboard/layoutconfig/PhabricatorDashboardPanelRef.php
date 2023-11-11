<?php

namespace orangins\modules\dashboard\layoutconfig;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorDashboardPanelRef
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelRef
    extends OranginsObject
{

    /**
     * @var
     */
    private $panelPHID;
    /**
     * @var
     */
    private $panelKey;
    /**
     * @var
     */
    private $columnKey;

    /**
     * @param $panel_phid
     * @return $this
     * @author 陈妙威
     */
    public function setPanelPHID($panel_phid)
    {
        $this->panelPHID = $panel_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanelPHID()
    {
        return $this->panelPHID;
    }

    /**
     * @param $column_key
     * @return $this
     * @author 陈妙威
     */
    public function setColumnKey($column_key)
    {
        $this->columnKey = $column_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getColumnKey()
    {
        return $this->columnKey;
    }

    /**
     * @param $panel_key
     * @return $this
     * @author 陈妙威
     */
    public function setPanelKey($panel_key)
    {
        $this->panelKey = $panel_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return $this->panelKey;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function toDictionary()
    {
        return array(
            'panelKey' => $this->getPanelKey(),
            'panelPHID' => $this->getPanelPHID(),
            'columnKey' => $this->getColumnKey(),
        );
    }
}
