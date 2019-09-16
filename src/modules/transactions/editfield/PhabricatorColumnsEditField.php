<?php

namespace orangins\modules\transactions\editfield;

final class PhabricatorColumnsEditField
    extends PhabricatorPHIDListEditField
{

    private $columnMap;

    public function setColumnMap(array $column_map)
    {
        $this->columnMap = $column_map;
        return $this;
    }

    public function getColumnMap()
    {
        return $this->columnMap;
    }

    protected function newControl()
    {
        $control = (new AphrontFormHandlesControl());
        $control->setIsInvisible(true);

        return $control;
    }

    protected function newHTTPParameterType()
    {
        return new AphrontPHIDListHTTPParameterType();
    }

    protected function newConduitParameterType()
    {
        return new ConduitColumnsParameterType();
    }

    protected function newCommentAction()
    {
        $column_map = $this->getColumnMap();
        if (!$column_map) {
            return null;
        }

        return (new PhabricatorEditEngineColumnsCommentAction())
            ->setColumnMap($this->getColumnMap());
    }

}
