<?php

namespace orangins\modules\file\actions;

use orangins\modules\file\editors\PhabricatorFileEditEngine;

/**
 * Class PhabricatorFileEditAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileEditAction
    extends PhabricatorFileAction
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
        return (new PhabricatorFileEditEngine())
            ->setAction($this)
            ->buildResponse();
    }
}
