<?php

namespace orangins\modules\transactions\bulk\type;

/**
 * Class BulkPointsParameterType
 * @package orangins\modules\transactions\bulk\type
 * @author 陈妙威
 */
final class BulkPointsParameterType
    extends BulkParameterType
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'points';
    }

}
