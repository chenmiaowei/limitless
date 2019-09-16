<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\AphrontRequest;
use orangins\modules\transactions\bulk\type\BulkTokenizerParameterType;
use orangins\modules\transactions\commentaction\PhabricatorEditEngineTokenizerCommentAction;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;

/**
 * Class PhabricatorTokenizerEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
abstract class PhabricatorTokenizerEditField extends PhabricatorPHIDListEditField
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newDatasource();

    /**
     * @author 陈妙威
     */
    protected function newControl()
    {
        $control = new AphrontFormTokenizerControl();
        $control->setDatasource($this->newDatasource());

        $initial_value = $this->getInitialValue();
        if ($initial_value !== null) {
            $control->setInitialValue($initial_value);
        }

        if ($this->getIsSingleValue()) {
            $control->setLimit(1);
        }


        return $control;
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return array
     * @author 陈妙威
     */
    protected function getInitialValueFromSubmit(AphrontRequest $request, $key)
    {
        return $request->getArr($key . '_initial');
    }


    /**
     * @return \orangins\modules\transactions\edittype\PhabricatorDatasourceEditType
     * @author 陈妙威
     */
    protected function newEditType()
    {
        $type = parent::newEditType();

        $datasource = $this->newDatasource()
            ->setViewer($this->getViewer());
        $type->setDatasource($datasource);

        return $type;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newCommentAction()
    {
        $viewer = $this->getViewer();

        $datasource = $this->newDatasource()
            ->setViewer($viewer);

        $action = (new PhabricatorEditEngineTokenizerCommentAction())
            ->setDatasource($datasource);

        if ($this->getIsSingleValue()) {
            $action->setLimit(1);
        }

        $initial_value = $this->getInitialValue();
        if ($initial_value !== null) {
            $action->setInitialValue($initial_value);
        }

        return $action;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        $datasource = $this->newDatasource()
            ->setViewer($this->getViewer());

        if ($this->getIsSingleValue()) {
            $datasource->setLimit(1);
        }

        return (new BulkTokenizerParameterType())
            ->setDatasource($datasource);
    }

}
