<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSubmitControl;

/**
 * Class PhabricatorSubmitEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorSubmitEditField
    extends PhabricatorEditField
{

    /**
     * @return
     * @author 陈妙威
     */
    protected function renderControl()
    {
        return (new AphrontFormSubmitControl())
            ->setValue($this->getValue());
    }

    /**
     * @return AphrontStringHTTPParameterType|null
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return null;
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return null;
    }
}
