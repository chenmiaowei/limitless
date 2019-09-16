<?php

namespace orangins\lib\infrastructure\customfield\storage;

use orangins\lib\db\ActiveRecord;

/**
 * Class PhabricatorCustomFieldIndexStorage
 * @package orangins\lib\infrastructure\customfield\storage
 * @author 陈妙威
 */
abstract class PhabricatorCustomFieldIndexStorage extends ActiveRecord
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function formatForInsert();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getIndexValueType();

}
