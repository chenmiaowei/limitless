<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormSelectControl;

/**
 * Class PhabricatorSearchOrderField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchOrderField
    extends PhabricatorSearchField
{

    /**
     * @var
     */
    private $options;
    /**
     * @var
     */
    private $orderAliases;

    /**
     * @param array $order_aliases
     * @return $this
     * @author 陈妙威
     */
    public function setOrderAliases(array $order_aliases)
    {
        $this->orderAliases = $order_aliases;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOrderAliases()
    {
        return $this->orderAliases;
    }

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
        return null;
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return mixed|null|string
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $request->getStr($key);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getValueForControl()
    {
        // If the SavedQuery has an alias for an order, map it to the canonical
        // name for the order so the correct option is selected in the dropdown.
        $value = parent::getValueForControl();
        if (isset($this->orderAliases[$value])) {
            $value = $this->orderAliases[$value];
        }
        return $value;
    }

    /**
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormSelectControl())
            ->setOptions($this->getOptions());
    }

}
