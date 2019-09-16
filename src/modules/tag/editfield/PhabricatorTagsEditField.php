<?php

namespace orangins\modules\tag\editfield;

use orangins\lib\request\httpparametertype\AphrontPHIDListHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontUserListHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDListParameterType;
use orangins\modules\conduit\parametertype\ConduitUserListParameterType;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableDatasource;
use orangins\modules\transactions\editfield\PhabricatorTokenizerEditField;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use PhutilInvalidStateException;

/**
 * Class PhabricatorSubscribersEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorTagsEditField extends PhabricatorTokenizerEditField
{

    /**
     * @var
     */
    private $datasource;



    /**
     * @param PhabricatorTypeaheadDatasource $datasource
     * @return $this
     * @author 陈妙威
     */
    public function setDatasource(PhabricatorTypeaheadDatasource $datasource)
    {
        $this->datasource = $datasource;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getDatasource()
    {
        if (!$this->datasource) {
            throw new PhutilInvalidStateException('setDatasource');
        }
        return $this->datasource;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newDatasource()
    {
        $datasource = clone $this->getDatasource();
        return ($datasource);
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
