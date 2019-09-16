<?php

namespace orangins\modules\dashboard\layoutconfig;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorDashboardColumn
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardColumn
    extends OranginsObject
{

    /**
     * @var
     */
    private $columnKey;
    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var array
     */
    private $refs = array();

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
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @param array $refs
     * @return $this
     * @author 陈妙威
     */
    public function setPanelRefs(array $refs)
    {
        assert_instances_of($refs, PhabricatorDashboardPanelRef::className());
        $this->refs = $refs;
        return $this;
    }

    /**
     * @param PhabricatorDashboardPanelRef $ref
     * @return $this
     * @author 陈妙威
     */
    public function addPanelRef(PhabricatorDashboardPanelRef $ref)
    {
        $this->refs[] = $ref;
        return $this;
    }

    /**
     * @return PhabricatorDashboardPanelRef[]
     * @author 陈妙威
     */
    public function getPanelRefs()
    {
        return $this->refs;
    }

}
