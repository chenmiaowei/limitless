<?php

namespace orangins\modules\herald\actions;

use AphrontDuplicateKeyQueryException;
use orangins\modules\herald\editors\HeraldWebhookEditEngine;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\InvalidConfigException;

/**
 * Class HeraldWebhookEditController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldWebhookEditController
    extends HeraldWebhookController
{

    /**
     * @return mixed
     * @throws AphrontDuplicateKeyQueryException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new HeraldWebhookEditEngine())
            ->setAction($this)
            ->buildResponse();
    }
}
