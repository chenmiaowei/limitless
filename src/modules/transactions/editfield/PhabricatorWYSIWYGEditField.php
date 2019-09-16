<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\view\form\control\PhabricatorRemarkupControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\transactions\bulk\type\BulkRemarkupParameterType;
use orangins\modules\transactions\bulk\type\BulkStringParameterType;
use orangins\modules\widgets\form\control\PhabricatorUEditorControl;

/**
 * Class PhabricatorRemarkupEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorWYSIWYGEditField
    extends PhabricatorEditField
{
    private $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return PhabricatorWYSIWYGEditField
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return PhabricatorUEditorControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new PhabricatorUEditorControl())->setId($this->id);
    }

    /**
     * @return ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return BulkStringParameterType
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return new BulkStringParameterType();
    }

}
