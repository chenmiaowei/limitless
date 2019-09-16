<?php

namespace orangins\modules\oauthserver\actions\client;

use orangins\modules\oauthserver\editor\PhabricatorOAuthServerEditEngine;

/**
 * Class PhabricatorOAuthClientEditController
 * @package orangins\modules\oauthserver\actions\client
 * @author 陈妙威
 */
final class PhabricatorOAuthClientEditController
    extends PhabricatorOAuthClientController
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
        return (new  PhabricatorOAuthServerEditEngine())
            ->setAction($this)
            ->buildResponse();
    }

}
