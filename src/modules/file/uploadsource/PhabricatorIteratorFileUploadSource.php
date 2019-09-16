<?php

namespace orangins\modules\file\uploadsource;

use Iterator;

/**
 * Class PhabricatorIteratorFileUploadSource
 * @package orangins\modules\file\uploadsource
 * @author 陈妙威
 */
final class PhabricatorIteratorFileUploadSource
    extends PhabricatorFileUploadSource
{

    /**
     * @var
     */
    private $iterator;

    /**
     * @param Iterator $iterator
     * @return $this
     * @author 陈妙威
     */
    public function setIterator(Iterator $iterator)
    {
        $this->iterator = $iterator;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIterator()
    {
        return $this->iterator;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newDataIterator()
    {
        return $this->getIterator();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getDataLength()
    {
        return null;
    }
}
