<?php

namespace orangins\modules\transactions\commentaction;

/**
 * Class PhabricatorEditEnginePointsCommentAction
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
final class PhabricatorEditEnginePointsCommentAction extends PhabricatorEditEngineCommentAction
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'points';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getPHUIXControlSpecification()
    {
        return array(
            'value' => $this->getValue(),
        );
    }

}
