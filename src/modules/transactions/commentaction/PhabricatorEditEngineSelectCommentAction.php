<?php

namespace orangins\modules\transactions\commentaction;

/**
 * Class PhabricatorEditEngineSelectCommentAction
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
final class PhabricatorEditEngineSelectCommentAction extends PhabricatorEditEngineCommentAction
{

    /**
     * @var
     */
    private $options;

    /**
     * @param array $options
     * @return $this
     * @author 陈妙威
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'select';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getPHUIXControlSpecification()
    {
        return array(
            'options' => $this->getOptions(),
            'order' => array_keys($this->getOptions()),
            'value' => $this->getValue(),
        );
    }

}
