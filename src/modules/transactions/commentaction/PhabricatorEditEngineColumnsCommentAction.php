<?php

namespace orangins\modules\transactions\commentaction;

/**
 * Class PhabricatorEditEngineColumnsCommentAction
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
final class PhabricatorEditEngineColumnsCommentAction extends PhabricatorEditEngineCommentAction
{

    /**
     * @var
     */
    private $columnMap;

    /**
     * @param array $column_map
     * @return $this
     * @author 陈妙威
     */
    public function setColumnMap(array $column_map)
    {
        $this->columnMap = $column_map;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getColumnMap()
    {
        return $this->columnMap;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'optgroups';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPHUIXControlSpecification()
    {
        return array(
            'groups' => $this->getColumnMap(),
        );
    }

}
