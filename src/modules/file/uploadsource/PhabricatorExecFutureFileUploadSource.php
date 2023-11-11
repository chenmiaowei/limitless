<?php

namespace orangins\modules\file\uploadsource;

use ExecFuture;
use LinesOfALargeExecFuture;

/**
 * Class PhabricatorExecFutureFileUploadSource
 * @package orangins\modules\file\uploadsource
 * @author 陈妙威
 */
final class PhabricatorExecFutureFileUploadSource
    extends PhabricatorFileUploadSource
{

    /**
     * @var
     */
    private $future;

    /**
     * @param ExecFuture $future
     * @return $this
     * @author 陈妙威
     */
    public function setExecFuture(ExecFuture $future)
    {
        $this->future = $future;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getExecFuture()
    {
        return $this->future;
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newDataIterator()
    {
        $future = $this->getExecFuture();

        return (new LinesOfALargeExecFuture($future))
            ->setDelimiter(null);
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    protected function getDataLength()
    {
        return null;
    }
}
