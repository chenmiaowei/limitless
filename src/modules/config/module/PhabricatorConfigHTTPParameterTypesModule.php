<?php

namespace orangins\modules\config\module;

use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontHTTPParameterType;
use orangins\modules\config\view\PhabricatorHTTPParameterTypeTableView;

/**
 * Class PhabricatorConfigHTTPParameterTypesModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
final class PhabricatorConfigHTTPParameterTypesModule
    extends PhabricatorConfigModule
{

    public function getModuleKey()
    {
        return 'httpparameter';
    }

    public function getModuleName()
    {
        return \Yii::t("app", 'HTTP Parameter Types');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $types = AphrontHTTPParameterType::getAllTypes();

        return (new PhabricatorHTTPParameterTypeTableView())
            ->setHTTPParameterTypes($types);
    }

}
