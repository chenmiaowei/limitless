<?php

namespace orangins\lib\infrastructure\customfield\storage;

abstract class PhabricatorCustomFieldStringIndexStorage
    extends PhabricatorCustomFieldIndexStorage
{

    public function formatForInsert()
    {
        return qsprintf(
            $conn,
            '(%s, %s, %s)',
            $this->getObjectPHID(),
            $this->getIndexKey(),
            $this->getIndexValue());
    }

    public function getIndexValueType()
    {
        return 'string';
    }
}
