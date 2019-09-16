<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditEngine;

/**
 * Class PhabricatorEditEngineConfigurationEditController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationEditController
    extends PhabricatorEditEngineController
{

    /**
     * @return Aphront404Response
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $target_engine_key = $request->getURIData('engineKey');

        $target_engine = PhabricatorEditEngine::getByKey(
            $viewer,
            $target_engine_key);
        if (!$target_engine) {
            return new Aphront404Response();
        }

        $this->setEngineKey($target_engine->getEngineKey());

        return (new PhabricatorEditEngineConfigurationEditEngine())
            ->setTargetEngine($target_engine)
            ->setAction($this)
            ->buildResponse();
    }
}
