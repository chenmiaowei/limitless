<?php

namespace orangins\modules\transactions\bulk\type;

/**
 * Class BulkStringParameterType
 * @package orangins\modules\transactions\bulk\type
 * @author 陈妙威
 */
final class BulkStringParameterType
    extends BulkParameterType
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'text';
    }

}
