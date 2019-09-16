<?php

namespace orangins\lib\view\form\control;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\modules\widgets\javelin\JavelinUniformControlAsset;

/**
 * Class AphrontFormFileControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormFileControl extends AphrontFormControl
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-file-text';
    }

    /**
     * @return mixed|string
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $id = JavelinHtml::generateUniqueNodeId();
        $color = PhabricatorEnv::getEnvConfig("ui.widget-color");
        JavelinHtml::initBehavior(new JavelinUniformControlAsset(), [
            'id' => $id,
            'options' => [
                'fileButtonClass' => "action btn bg-{$color}",
                'selectClass' => "uniform-select bg-{$color}-400 border-{$color}-400",
                'fileDefaultHtml' => '请选择文件',
                'fileButtonHtml' => '选择文件',
            ]
        ]);
        return JavelinHtml::phutil_tag(
            'input',
            array(
                "id" => $id,
                'type' => 'file',
                'name' => $this->getName(),
                'disabled' => $this->getDisabled() ? 'disabled' : null,
            ));
    }
}
