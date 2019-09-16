<?php

namespace orangins\modules\transactions\editfield;

use PhutilInvalidStateException;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class PhabricatorDatasourceEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorDatasourceEditField extends PhabricatorTokenizerEditField
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
}
