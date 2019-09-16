<?php

namespace orangins\modules\transactions\editfield;

final class PhabricatorCheckboxesEditField
    extends PhabricatorEditField
{

    private $options;

    protected function newControl()
    {
        $options = $this->getOptions();

        return (new AphrontFormCheckboxControl())
            ->setOptions($options);
    }

    protected function newConduitParameterType()
    {
        return new ConduitStringListParameterType();
    }

    protected function newHTTPParameterType()
    {
        return new AphrontStringListHTTPParameterType();
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        if ($this->options === null) {
            throw new PhutilInvalidStateException('setOptions');
        }

        return $this->options;
    }

}
