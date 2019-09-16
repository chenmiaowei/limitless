<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\view\form\control\AphrontFormMarkupControl;

/**
 * Class PhabricatorStaticEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorStaticEditField
    extends PhabricatorEditField
{

    /**
     * @return AphrontFormMarkupControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return new AphrontFormMarkupControl();
    }

    /**
     * @return null|\orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType
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
