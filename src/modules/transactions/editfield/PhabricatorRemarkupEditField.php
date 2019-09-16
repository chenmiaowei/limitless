<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\view\form\control\PhabricatorRemarkupControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\transactions\bulk\type\BulkRemarkupParameterType;

/**
 * Class PhabricatorRemarkupEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorRemarkupEditField
    extends PhabricatorEditField
{

    /**
     * @return PhabricatorRemarkupControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return new PhabricatorRemarkupControl();
    }

    /**
     * @return mixed|ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return null|BulkRemarkupParameterType
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return new BulkRemarkupParameterType();
    }

}
