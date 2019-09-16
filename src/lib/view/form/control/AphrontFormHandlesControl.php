<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;

/**
 * Class AphrontFormHandlesControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormHandlesControl extends AphrontFormControl
{

    /**
     * @var
     */
    private $isInvisible;

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-handles';
    }

    /**
     * @param $is_invisible
     * @return $this
     * @author 陈妙威
     */
    public function setIsInvisible($is_invisible)
    {
        $this->isInvisible = $is_invisible;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsInvisible()
    {
        return $this->isInvisible;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldRender()
    {
        return (bool)$this->getValue();
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getLabel()
    {
        // TODO: This is a bit funky and still rendering a few pixels of padding
        // on the form, but there's currently no way to get a control to only emit
        // hidden inputs. Clean this up eventually.

        if ($this->getIsInvisible()) {
            return null;
        }

        return parent::getLabel();
    }

    /**
     * @return array|mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $value = $this->getValue();
        $viewer = $this->getUser();

        $out = array();

        if (!$this->getIsInvisible()) {
            $list = $viewer->renderHandleList($value);
            $list = (new PHUIBoxView())
                ->addPadding(PHUI::PADDING_SMALL_TOP)
                ->appendChild($list);
            $out[] = $list;
        }

        $inputs = array();
        foreach ($value as $phid) {
            $inputs[] = JavelinHtml::phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => $this->getName() . '[]',
                    'value' => $phid,
                ));
        }
        $out[] = $inputs;

        return $out;
    }

}
