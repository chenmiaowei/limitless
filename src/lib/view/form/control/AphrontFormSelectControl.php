<?php

namespace orangins\lib\view\form\control;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\widgets\javelin\JavelinSelect2Asset;
use yii\helpers\ArrayHelper;

/**
 * Class AphrontFormSelectControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormSelectControl extends AphrontFormControl
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
    private $data;

    /**
     * @var bool
     */
    private $enableSelect2 = true;

    /**
     * @var array
     */
    private $disabledOptions = array();

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
     * @param bool $enableSelect2
     * @return self
     */
    public function setEnableSelect2($enableSelect2)
    {
        $this->enableSelect2 = $enableSelect2;
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
     * @param array $disabled
     * @return $this
     * @author 陈妙威
     */
    public function setDisabledOptions(array $disabled)
    {
        $this->disabledOptions = $disabled;
        return $this;
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
        return self::renderSelectTag(
            $this->getValue(),
            $this->getOptions(),
            array(
                'name' => $this->getName(),
                'disabled' => $this->getDisabled() ? 'disabled' : null,
                'id' => $this->getID(),
                'class' => 'form-control'
            ),
            $this->disabledOptions,
            $this->enableSelect2);
    }

    /**
     * @param $selected
     * @param array $options
     * @param array $attrs
     * @param array $disabled
     * @param bool $enableSelect2
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function renderSelectTag(
        $selected,
        array $options,
        array $attrs = array(),
        array $disabled = array(),
        $enableSelect2 = true)
    {
        $option_tags = self::renderOptions($selected, $options, $disabled);

        if ($enableSelect2) {
            JavelinHtml::initBehavior(new JavelinSelect2Asset(), [
                'id' => ArrayHelper::getValue($attrs, 'id'),
                'options' => [
                    'width' => '100%',
                    'minimumResultsForSearch'=> 20,
                ]
            ]);
        }

        return JavelinHtml::phutil_tag('select', $attrs, $option_tags);
    }

    /**
     * @param $selected
     * @param array $options
     * @param array $disabled
     * @return array
     * @throws \yii\base\Exception*@throws Exception
     * @throws Exception
     * @author 陈妙威
     */
    private static function renderOptions(
        $selected,
        array $options,
        array $disabled = array())
    {
        $disabled = OranginsUtil::array_fuse($disabled);

        $tags = array();
        $already_selected = false;
        foreach ($options as $value => $thing) {
            if (is_array($thing)) {
                $tags[] = JavelinHtml::phutil_tag('optgroup', array(
                    'label' => $value,
                ), self::renderOptions($selected, $thing));
            } else {
                // When there are a list of options including similar values like
                // "0" and "" (the empty string), only select the first matching
                // value. Ideally this should be more precise about matching, but we
                // have 2,000 of these controls at this point so hold that for a
                // broader rewrite.
                if (!$already_selected && ($value == $selected)) {
                    $is_selected = 'selected';
                    $already_selected = true;
                } else {
                    $is_selected = null;
                }

                $tags[] = JavelinHtml::phutil_tag('option', array(
                    'selected' => $is_selected,
                    'value' => $value,
                    'disabled' => isset($disabled[$value]) ? 'disabled' : null,
                ), $thing);
            }
        }
        return $tags;
    }

}
