<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontPHIDListHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontUserListHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDListParameterType;
use orangins\modules\conduit\parametertype\ConduitUserListParameterType;
use orangins\modules\conduit\parametertype\ConduitUserParameterType;
use orangins\modules\people\typeahead\PhabricatorPeopleDatasource;

/**
 * Class PhabricatorUsersEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorUsersEditField extends PhabricatorTokenizerEditField
{

    /**
     * @return PhabricatorPeopleDatasource|mixed
     * @author 陈妙威
     */
    protected function newDatasource()
    {
        return new PhabricatorPeopleDatasource();
    }

    /**
     * @return AphrontPHIDListHTTPParameterType|AphrontStringHTTPParameterType|AphrontUserListHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontUserListHTTPParameterType();
    }

    /**
     * @return ConduitPHIDListParameterType|ConduitUserListParameterType|ConduitUserParameterType|mixed
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        if ($this->getIsSingleValue()) {
            return new ConduitUserParameterType();
        } else {
            return new ConduitUserListParameterType();
        }
    }

}
