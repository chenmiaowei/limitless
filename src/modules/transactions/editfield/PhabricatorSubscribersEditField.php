<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontPHIDListHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontUserListHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDListParameterType;
use orangins\modules\conduit\parametertype\ConduitUserListParameterType;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableDatasource;

/**
 * Class PhabricatorSubscribersEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorSubscribersEditField extends PhabricatorTokenizerEditField
{

    /**
     * @return PhabricatorMetaMTAMailableDatasource|mixed
     * @author 陈妙威
     */
    protected function newDatasource()
    {
        return new PhabricatorMetaMTAMailableDatasource();
    }

    /**
     * @return AphrontPHIDListHTTPParameterType|AphrontStringHTTPParameterType|AphrontUserListHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        // TODO: Implement a more expansive "Mailable" parameter type which
        // accepts users or projects.
        return new AphrontUserListHTTPParameterType();
    }

    /**
     * @return ConduitPHIDListParameterType|ConduitUserListParameterType|mixed
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitUserListParameterType();
    }
}
