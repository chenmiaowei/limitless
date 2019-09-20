<?php

namespace orangins\modules\file\conduit;

use orangins\modules\conduit\protocol\ConduitAPIRequest;

/**
 * Class FileUploadHashConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
final class FileUploadHashConduitAPIMethod extends FileConduitAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'file.uploadhash';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodStatus()
    {
        return self::METHOD_STATUS_DEPRECATED;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMethodStatusDescription()
    {
        return pht(
            'This method is deprecated. Callers should use "file.allocate" ' .
            'instead.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return pht('Obsolete. Has no effect.');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'hash' => 'required nonempty string',
            'name' => 'required nonempty string',
        );
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'null';
    }

    /**
     * @param ConduitAPIRequest $request
     * @return mixed|null
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        return null;
    }

}
