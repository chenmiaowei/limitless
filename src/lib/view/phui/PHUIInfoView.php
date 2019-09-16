<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIInfoView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIInfoView extends AphrontTagView
{

    /**
     *
     */
    const SEVERITY_ERROR = 'danger';
    /**
     *
     */
    const SEVERITY_WARNING = 'warning';
    /**
     *
     */
    const SEVERITY_NOTICE = 'primary';
    /**
     *
     */
    const SEVERITY_NODATA = 'primary';
    /**
     *
     */
    const SEVERITY_SUCCESS = 'success';
    /**
     *
     */
    const SEVERITY_PLAIN = 'info';

    /**
     * @var
     */
    private $title;
    /**
     * @var array
     */
    private $errors = array();
    /**
     * @var null
     */
    private $severity = null;
    /**
     * @var
     */
    private $id;
    /**
     * @var array
     */
    private $buttons = array();
    /**
     * @var
     */
    private $isHidden;
    /**
     * @var
     */
    private $flush;
    /**
     * @var
     */
    private $icon;

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $severity
     * @return $this
     * @author 陈妙威
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    private function getSeverity()
    {
        $severity = $this->severity ? $this->severity : self::SEVERITY_ERROR;
        return $severity;
    }

    /**
     * @param array $errors
     * @return $this
     * @author 陈妙威
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }


    /**
     * @param $bool
     * @return $this
     * @author 陈妙威
     */
    public function setIsHidden($bool)
    {
        $this->isHidden = $bool;
        return $this;
    }

    /**
     * @param $flush
     * @return $this
     * @author 陈妙威
     */
    public function setFlush($flush)
    {
        $this->flush = $flush;
        return $this;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        if ($icon instanceof PHUIIconView) {
            $this->icon = $icon;
        } else {
            $icon = (new PHUIIconView())
                ->setIcon($icon);
            $this->icon = $icon;
        }

        return $this;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    private function getIcon()
    {
        if ($this->icon) {
            return $this->icon;
        }

        switch ($this->getSeverity()) {
            case self::SEVERITY_ERROR:
                $icon = 'fa-exclamation-circle';
                break;
            case self::SEVERITY_WARNING:
                $icon = 'fa-exclamation-triangle';
                break;
            case self::SEVERITY_NOTICE:
                $icon = 'fa-info-circle';
                break;
            case self::SEVERITY_PLAIN:
            case self::SEVERITY_NODATA:
                return null;
                break;
            case self::SEVERITY_SUCCESS:
                $icon = 'fa-check-circle';
                break;
        }

        $icon = (new PHUIIconView())
            ->setIcon($icon)
            ->addClass('phui-info-icon');
        return $icon;
    }

    /**
     * @param PHUIButtonView $button
     * @return $this
     * @author 陈妙威
     */
    public function addButton(PHUIButtonView $button)
    {
        $this->buttons[] = $button;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'alert alert-styled-left';
        $classes[] = 'alert-' . $this->getSeverity();
        if ($this->flush) {
            $classes[] = 'phui-info-view-flush';
        }
        return array(
            'id' => $this->id,
            'class' => implode(' ', $classes),
            'style' => $this->isHidden ? 'display: none;' : null,
        );
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//    require_celerity_resource('phui-info-view-css');

        $errors = $this->errors;
        if (count($errors) > 1) {
            $list = array();
            foreach ($errors as $error) {
                $list[] = JavelinHtml::phutil_tag(
                    'li',
                    array(),
                    $error);
            }
            $list = JavelinHtml::phutil_tag(
                'ul',
                array(
                    'class' => 'phui-info-view-list m-0',
                ),
                $list);
        } else if (count($errors) == 1) {
            $list = OranginsUtil::head($this->errors);
        } else {
            $list = null;
        }

        $title = $this->title;
        if (strlen($title)) {
            $title = JavelinHtml::phutil_tag(
                'h1',
                array(
                    'class' => 'font-weight-semibold',
                ),
                $title);
        } else {
            $title = null;
        }

        $children = $this->renderChildren();
        if ($list) {
            $children[] = $list;
        }

        $body = null;
        if (!empty($children)) {
            $body = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-info-view-body',
                ),
                $children);
        }

        $buttons = null;
        if (!empty($this->buttons)) {
            $buttons = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-info-view-actions',
                ),
                $this->buttons);
        }

        return array(
            $buttons,
            $title,
            $body,
        );
    }
}
