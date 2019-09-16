<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;

/**
 * Class AphrontFormTextWithSubmitControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormTextWithSubmitControl extends AphrontFormControl
{

    /**
     * @var
     */
    private $submitLabel;

    /**
     * @param $submit_label
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitLabel($submit_label)
    {
        $this->submitLabel = $submit_label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubmitLabel()
    {
        return $this->submitLabel;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-text-with-submit';
    }

    /**
     * @return mixed|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'text-with-submit-control-outer-bounds',
            ),
            array(
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'text-with-submit-control-text-bounds',
                    ),
                    JavelinHtml::phutil_tag(
                        'input',
                        array(
                            'type' => 'text',
                            'class' => 'text-with-submit-control-text',
                            'name' => $this->getName(),
                            'value' => $this->getValue(),
                            'disabled' => $this->getDisabled() ? 'disabled' : null,
                            'id' => $this->getID(),
                        ))),
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'text-with-submit-control-submit-bounds',
                    ),
                    JavelinHtml::phutil_tag(
                        'input',
                        array(
                            'type' => 'submit',
                            'class' => 'text-with-submit-control-submit grey',
                            'value' => coalesce($this->getSubmitLabel(), \Yii::t("app", 'Submit')),
                        ))),
            ));
    }

}
