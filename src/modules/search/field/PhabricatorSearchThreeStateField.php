<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\conduit\parametertype\ConduitBoolParameterType;

/**
 * Class PhabricatorSearchThreeStateField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchThreeStateField  extends PhabricatorSearchField
{

    /**
     * @var
     */
    private $options;

    /**
     * @param $null
     * @param $yes
     * @param $no
     * @return $this
     * @author 陈妙威
     */
    public function setOptions($null, $yes, $no)
    {
        $this->options = array(
            '' => $null,
            'true' => $yes,
            'false' => $no,
        );
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
     * @return bool|mixed|null
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        if (!strlen($request->getStr($key))) {
            return null;
        }
        return $request->getBool($key);
    }

    /**
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormSelectControl())
            ->setOptions($this->getOptions());
    }

    /**
     * @return mixed|null|string
     * @author 陈妙威
     */
    protected function getValueForControl()
    {
        $value = parent::getValueForControl();
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        return null;
    }

    /**
     * @return ConduitBoolParameterType|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitBoolParameterType();
    }

}
