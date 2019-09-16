<?php

namespace orangins\modules\transactions\commentaction;

/**
 * Class PhabricatorEditEngineStaticCommentAction
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
final class PhabricatorEditEngineStaticCommentAction extends PhabricatorEditEngineCommentAction
{

    /**
     * @var
     */
    private $description;

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'static';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getPHUIXControlSpecification()
    {
        return array(
            'value' => $this->getValue(),
            'description' => $this->getDescription(),
        );
    }

}
