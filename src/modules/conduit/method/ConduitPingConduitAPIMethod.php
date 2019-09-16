<?php

namespace orangins\modules\conduit\method;

use orangins\modules\conduit\protocol\ConduitAPIRequest;

/**
 * Class ConduitPingConduitAPIMethod
 * @package orangins\modules\conduit\method
 * @author 陈妙威
 */
final class ConduitPingConduitAPIMethod extends ConduitAPIMethod
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'conduit.ping';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAuthentication()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return \Yii::t("app",'Basic ping for monitoring or a health-check.');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'string';
    }

    /**
     * @param ConduitAPIRequest $request
     * @return string
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        return php_uname('n');
    }

}
