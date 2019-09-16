<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\AphrontFormView;

/**
 * Class PhabricatorInstructionsEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorInstructionsEditField extends PhabricatorEditField
{

    /**
     * @param AphrontFormView $form
     * @return PhabricatorEditField
     * @author 陈妙威
     */
    public function appendToForm(AphrontFormView $form)
    {
        return $form->appendRemarkupInstructions($this->getValue());
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
