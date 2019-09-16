<?php

namespace orangins\modules\transactions\bulk\type;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\editfield\PhabricatorEditField;

/**
 * Class BulkParameterType
 * @package orangins\modules\transactions\bulk\type
 * @author 陈妙威
 */
abstract class BulkParameterType extends \orangins\lib\OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $field;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorEditField $field
     * @return $this
     * @author 陈妙威
     */
    final public function setField(PhabricatorEditField $field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getField()
    {
        return $this->field;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPHUIXControlType();

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPHUIXControlSpecification()
    {
        return array(
            'value' => null,
        );
    }

}
