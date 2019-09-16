<?php

namespace orangins\modules\search\field;

use orangins\modules\conduit\parametertype\ConduitParameterType;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class PhabricatorSearchDatasourceField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchDatasourceField
    extends PhabricatorSearchTokenizerField
{

    /**
     * @var
     */
    private $datasource;
    /**
     * @var
     */
    private $conduitParameterType;

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newDatasource()
    {
        $x = clone $this->datasource;
        return ($x);
    }

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
     * @param ConduitParameterType $type
     * @return $this
     * @author 陈妙威
     */
    public function setConduitParameterType(ConduitParameterType $type)
    {
        $this->conduitParameterType = $type;
        return $this;
    }

    /**
     * @return null|ConduitStringListParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        if (!$this->conduitParameterType) {
            return (new ConduitStringListParameterType())
                ->setAllowEmptyList(false);
        }

        return $this->conduitParameterType;
    }
}
