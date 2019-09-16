<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitPointsParameterType;
use orangins\modules\transactions\bulk\type\BulkPointsParameterType;
use orangins\modules\transactions\commentaction\PhabricatorEditEnginePointsCommentAction;

/**
 * Class PhabricatorPointsEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorPointsEditField
    extends PhabricatorEditField
{

    /**
     * @return AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return new AphrontFormTextControl();
    }

    /**
     * @return mixed|ConduitPointsParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitPointsParameterType();
    }

    /**
     * @return null|PhabricatorEditEnginePointsCommentAction
     * @author 陈妙威
     */
    protected function newCommentAction()
    {
        return (new PhabricatorEditEnginePointsCommentAction());
    }

    /**
     * @return null|BulkPointsParameterType
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return new BulkPointsParameterType();
    }

}
