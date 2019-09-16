<?php

namespace orangins\modules\transactions\bulk\type;

/**
 * Class BulkSelectParameterType
 * @package orangins\modules\transactions\bulk\type
 * @author 陈妙威
 */
final class BulkSelectParameterType
    extends BulkParameterType
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
     * @return string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'select';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPHUIXControlSpecification()
    {
        return array(
            'options' => $this->getOptions(),
            'order' => array_keys($this->getOptions()),
            'value' => null,
        );
    }

}
