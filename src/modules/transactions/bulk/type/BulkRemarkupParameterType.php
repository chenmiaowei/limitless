<?php

namespace orangins\modules\transactions\bulk\type;

/**
 * Class BulkRemarkupParameterType
 * @package orangins\modules\transactions\bulk\type
 * @author 陈妙威
 */
final class BulkRemarkupParameterType
    extends BulkParameterType
{

    public function getPHUIXControlType()
    {
        return 'remarkup';
    }

}
