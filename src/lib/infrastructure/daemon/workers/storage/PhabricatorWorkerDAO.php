<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use orangins\lib\db\ActiveRecord;

/**
 * Class PhabricatorWorkerDAO
 * @package orangins\lib\infrastructure\daemon\workers\storage
 * @author 陈妙威
 */
abstract class PhabricatorWorkerDAO extends ActiveRecord
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'worker';
    }

}
