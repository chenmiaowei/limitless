<?php

namespace orangins\modules\transactions\editfield;

use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\transactions\bulk\type\BulkStringParameterType;
use orangins\lib\view\form\control\AphrontFormTextControl;

/**
 * Class PhabricatorTextEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorTextEditField extends PhabricatorEditField
{

    /**
     * @var
     */
    private $placeholder;

    /**
     * @param $placeholder
     * @return $this
     * @author 陈妙威
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @return AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormTextControl())->setPlaceholder($this->placeholder);
    }

    /**
     * @return ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return BulkStringParameterType|null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return new BulkStringParameterType();
    }
}
