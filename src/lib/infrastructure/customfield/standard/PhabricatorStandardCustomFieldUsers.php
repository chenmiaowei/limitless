<?php

namespace orangins\lib\infrastructure\customfield\standard;

use orangins\lib\request\httpparametertype\AphrontPHIDListHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontUserListHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDListParameterType;
use orangins\modules\conduit\parametertype\ConduitUserListParameterType;
use orangins\modules\people\typeahead\PhabricatorPeopleDatasource;

/**
 * Class PhabricatorStandardCustomFieldUsers
 * @package orangins\lib\infrastructure\customfield\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldUsers
    extends PhabricatorStandardCustomFieldTokenizer
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'users';
    }

    /**
     * @return mixed|PhabricatorPeopleDatasource
     * @author 陈妙威
     */
    public function getDatasource()
    {
        return new PhabricatorPeopleDatasource();
    }

    /**
     * @return AphrontPHIDListHTTPParameterType|AphrontUserListHTTPParameterType|null
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontUserListHTTPParameterType();
    }

    /**
     * @return ConduitPHIDListParameterType|ConduitUserListParameterType|null
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        return new ConduitUserListParameterType();
    }

    /**
     * @return ConduitPHIDListParameterType|ConduitUserListParameterType|null
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitUserListParameterType();
    }
}
