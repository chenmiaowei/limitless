<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormSelectControl;

/**
 * Class PhabricatorSearchSelectField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchSelectField
    extends PhabricatorSearchField
{

    /**
     * @var
     */
    private $options;
    /**
     * @var
     */
    private $default;

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
     * @return null
     * @author 陈妙威
     */
    protected function getDefaultValue()
    {
        return $this->default;
    }

    /**
     * @param $default
     * @return $this
     * @author 陈妙威
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return mixed|string|null
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $request->getStr($key);
    }

    /**
     * @return AphrontFormSelectControl|void
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormSelectControl())
            ->setOptions($this->getOptions());
    }

}
