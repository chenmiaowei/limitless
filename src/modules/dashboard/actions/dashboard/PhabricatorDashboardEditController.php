<?php

namespace orangins\modules\dashboard\actions\dashboard;

use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\editors\PhabricatorDashboardEditEngine;

/**
 * Class PhabricatorDashboardEditController
 * @package orangins\modules\dashboard\actions\dashboard
 * @author 陈妙威
 */
final class PhabricatorDashboardEditController
    extends PhabricatorDashboardController
{

    /**
     * @return mixed
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new PhabricatorDashboardEditEngine())
            ->setAction($this)
            ->buildResponse();
    }
}
