<?php

namespace orangins\lib\view\form\control;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\modules\widgets\javelin\JavelinSwitchAsset;
use yii\helpers\ArrayHelper;

/**
 * Class AphrontFormRadioButtonControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormSwitchControl extends AphrontFormControl
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-select';
    }

    /**
     * @var
     */
    private $data = [
        'Disable',
        'Enable',
    ];

    /**
     * @param array $options
     * @return $this
     * @author 陈妙威
     */
    public function setOptions(array $options)
    {
        $this->data = $options;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOptions()
    {
        return $this->data;
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        $ID = parent::getID();
        if (!$ID) {
            $ID = JavelinHtml::generateUniqueNodeId();
            parent::setID($ID);
        }
        return $ID;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        JavelinHtml::initBehavior(new JavelinSwitchAsset(), [
            'id' => $this->getID(),
            'options' => [
                'disabled' => $this->getDisabled(),
            ]
        ]);

        $inputCheck = [
            "class" => "form-check-input-switchery",
            "data-fouc" => "",
            "type" => "checkbox",
            "name" => $this->getName()
        ];
        if($this->getValue()) {
            $inputCheck['checked'] = '';
        }
        return JavelinHtml::phutil_tag("div", [
            'class' => 'form-check form-check-switchery form-check-switchery-double',
            'id' => $this->getID(),
        ], [
            JavelinHtml::phutil_tag("label", [
                'class' => 'form-check-label'
            ], [
                ArrayHelper::getValue($this->data, 0, 'Disable'),
                JavelinHtml::phutil_tag("input", $inputCheck),
                ArrayHelper::getValue($this->data, 1, 'Enable'),
            ])
        ]);
    }
}
