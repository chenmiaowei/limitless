<?php

namespace orangins\modules\conduit\method;

use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\conduit\query\PhabricatorConduitMethodQuery;

/**
 * Class ConduitQueryConduitAPIMethod
 * @package orangins\modules\conduit\method
 * @author 陈妙威
 */
final class ConduitQueryConduitAPIMethod extends ConduitAPIMethod
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'conduit.query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return \Yii::t("app",'Returns the parameters of the Conduit methods.');
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
        return 'dict<dict>';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequiredScope()
    {
        return self::SCOPE_ALWAYS;
    }

    /**
     * @param ConduitAPIRequest $request
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $methods = (new PhabricatorConduitMethodQuery())
            ->setViewer($request->getUser())
            ->execute();

        $map = array();
        foreach ($methods as $method) {
            $map[$method->getAPIMethodName()] = array(
                'description' => $method->getMethodDescription(),
                'params' => $method->getParamTypes(),
                'return' => $method->getReturnType(),
            );
        }

        return $map;
    }

}
