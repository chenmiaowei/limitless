<?php

namespace orangins\modules\transactions\commentaction;

/**
 * Class PhabricatorEditEngineCheckboxesCommentAction
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
final class PhabricatorEditEngineCheckboxesCommentAction extends PhabricatorEditEngineCommentAction
{

    /**
     * @var array
     */
    private $options = array();

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
     * @return array
     * @author 陈妙威
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'checkboxes';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPHUIXControlSpecification()
    {
        $options = $this->getOptions();

        $labels = array();
        foreach ($options as $key => $option) {
            $labels[$key] = JavelinHtml::hsprintf('%s', $option);
        }

        return array(
            'value' => $this->getValue(),
            'keys' => array_keys($options),
            'labels' => $labels,
        );
    }

}
