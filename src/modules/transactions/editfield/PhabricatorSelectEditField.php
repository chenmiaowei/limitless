<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontSelectHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\transactions\bulk\type\BulkSelectParameterType;
use orangins\modules\transactions\commentaction\PhabricatorEditEngineSelectCommentAction;
use PhutilInvalidStateException;

/**
 * Class PhabricatorSelectEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorSelectEditField
    extends PhabricatorEditField
{

    /**
     * @var
     */
    private $options;
    /**
     * @var array
     */
    private $optionAliases = array();

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
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getOptions()
    {
        if ($this->options === null) {
            throw new PhutilInvalidStateException('setOptions');
        }
        return $this->options;
    }

    /**
     * @param array $option_aliases
     * @return $this
     * @author 陈妙威
     */
    public function setOptionAliases(array $option_aliases)
    {
        $this->optionAliases = $option_aliases;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getOptionAliases()
    {
        return $this->optionAliases;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    protected function getDefaultValueFromConfiguration($value)
    {
        return $this->getCanonicalValue($value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    protected function getValueForControl()
    {
        $value = parent::getValueForControl();
        return $this->getCanonicalValue($value);
    }

    /**
     * @return AphrontFormSelectControl
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormSelectControl())
            ->setOptions($this->getOptions());
    }

    /**
     * @return AphrontSelectHTTPParameterType|\orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontSelectHTTPParameterType();
    }

    /**
     * @return null|PhabricatorEditEngineSelectCommentAction
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newCommentAction()
    {
        return (new PhabricatorEditEngineSelectCommentAction())
            ->setOptions($this->getOptions());
    }

    /**
     * @return mixed|ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return null
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return (new BulkSelectParameterType())
            ->setOptions($this->getOptions());
    }

    /**
     * @param $value
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getCanonicalValue($value)
    {
        $options = $this->getOptions();
        if (!isset($options[$value])) {
            $aliases = $this->getOptionAliases();
            if (isset($aliases[$value])) {
                $value = $aliases[$value];
            }
        }

        return $value;
    }

}
