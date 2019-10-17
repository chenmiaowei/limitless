<?php

namespace orangins\lib\view\phui;

use orangins\lib\events\constant\PhabricatorEventType;
use orangins\lib\events\WillRenderPropertyEvent;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\AphrontView;
use orangins\lib\view\widget\AphrontKeyboardShortcutsAvailableView;
use PhutilSafeHTML;
use Yii;
use Exception;

/**
 * Class PHUIPropertyListView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIPropertyListView extends AphrontView
{

    /**
     * @var array
     */
    private $parts = array();
    /**
     * @var
     */
    private $hasKeyboardShortcuts;
    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $invokedWillRenderEvent;
    /**
     * @var null
     */
    private $actionList = null;
    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var
     */
    private $stacked;

    /**
     *
     */
    const ICON_SUMMARY = 'fa-align-left';
    /**
     *
     */
    const ICON_TESTPLAN = 'fa-file-text-o';

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @param PhabricatorActionListView $list
     * @return $this
     * @author 陈妙威
     */
    public function setActionList(PhabricatorActionListView $list)
    {
        $this->actionList = $list;
        return $this;
    }

    /**
     * @return PhabricatorActionListView
     * @author 陈妙威
     */
    public function getActionList()
    {
        return $this->actionList;
    }

    /**
     * @param $stacked
     * @return $this
     * @author 陈妙威
     */
    public function setStacked($stacked)
    {
        $this->stacked = $stacked;
        return $this;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param $has_keyboard_shortcuts
     * @return $this
     * @author 陈妙威
     */
    public function setHasKeyboardShortcuts($has_keyboard_shortcuts)
    {
        $this->hasKeyboardShortcuts = $has_keyboard_shortcuts;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function addProperty($key, $value)
    {
        $current = array_pop($this->parts);

        if (!$current || $current['type'] != 'property') {
            if ($current) {
                $this->parts[] = $current;
            }
            $current = array(
                'type' => 'property',
                'list' => array(),
            );
        }

        $current['list'][] = array(
            'key' => $key,
            'value' => $value,
        );

        $this->parts[] = $current;
        return $this;
    }

    /**
     * @param $name
     * @param null $icon
     * @return $this
     * @author 陈妙威
     */
    public function addSectionHeader($name, $icon = null)
    {
        $this->parts[] = array(
            'type' => 'section',
            'name' => $name,
            'icon' => $icon,
        );
        return $this;
    }

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function addTextContent($content)
    {
        $this->parts[] = array(
            'type' => 'text',
            'content' => $content,
        );
        return $this;
    }

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function addRawContent($content)
    {
        $this->parts[] = array(
            'type' => 'raw',
            'content' => $content,
        );
        return $this;
    }

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function addImageContent($content)
    {
        $this->parts[] = array(
            'type' => 'image',
            'content' => $content,
        );
        return $this;
    }

    /**
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function invokeWillRenderEvent()
    {
        if ($this->object && $this->getUser() && !$this->invokedWillRenderEvent) {
            $event = new WillRenderPropertyEvent();
            $event->setUser($this->getUser());
            $event->setView($this);
            $event->setObject( $this->object);
            Yii::$app->trigger(PhabricatorEventType::TYPE_UI_WILLRENDERPROPERTIES, $event);
        }
        $this->invokedWillRenderEvent = true;
    }

    /**
     * @return bool
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function hasAnyProperties()
    {
        $this->invokeWillRenderEvent();

        if ($this->parts) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {
        $this->invokeWillRenderEvent();

        $items = array();

        $parts = $this->parts;

        // If we have an action list, make sure we render a property part, even
        // if there are no properties. Otherwise, the action list won't render.
        if ($this->actionList) {
            $this->classes[] = 'phui-property-list-has-actions';
            $have_property_part = false;
            foreach ($this->parts as $part) {
                if ($part['type'] == 'property') {
                    $have_property_part = true;
                    break;
                }
            }
            if (!$have_property_part) {
                $parts[] = array(
                    'type' => 'property',
                    'list' => array(),
                );
            }
        }

        foreach ($parts as $part) {
            $type = $part['type'];
            switch ($type) {
                case 'property':
                    $items[] = $this->renderPropertyPart($part);
                    break;
                case 'section':
                    $items[] = $this->renderSectionPart($part);
                    break;
                case 'text':
                case 'image':
                    $items[] = $this->renderTextPart($part);
                    break;
                case 'raw':
                    $items[] = $this->renderRawPart($part);
                    break;
                default:
                    throw new Exception(\Yii::t("app", "Unknown part type '{0}'!", [
                        $type
                    ]));
            }
        }
        $this->classes[] = 'phui-property-list-section';
        $classes = implode(' ', $this->classes);

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => $classes,
            ),
            array(
                $items,
            ));
    }

    /**
     * @param array $part
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    private function renderPropertyPart(array $part)
    {
        $items = array();
        foreach ($part['list'] as $spec) {
            $key = $spec['key'];
            $value = $spec['value'];

            // NOTE: We append a space to each value to improve the behavior when the
            // user double-clicks a property value (like a URI) to select it. Without
            // the space, the label is also selected.

            $items[] = JavelinHtml::phutil_tag(
                'dt',
                array(
                    'class' => 'col-md-3 phui-property-list-key',
                ),
                array($key, ' '));

            $items[] = JavelinHtml::phutil_tag(
                'dd',
                array(
                    'class' => 'col-md-9 phui-property-list-value',
                ),
                array($value, ' '));
        }

        $stacked = '';
        if ($this->stacked) {
            $stacked = 'phui-property-list-stacked';
        }

        $list = JavelinHtml::phutil_tag(
            'dl',
            array(
                'class' => 'row phui-property-list-properties',
            ),
            $items);

        $shortcuts = null;
        if ($this->hasKeyboardShortcuts) {
            $shortcuts = new AphrontKeyboardShortcutsAvailableView();
        }

        $list = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-property-list-properties-wrap ' . $stacked,
            ),
            array($shortcuts, $list));

        $action_list = null;
        if ($this->actionList) {
            $action_list = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-property-list-actions',
                ),
                $this->actionList);
            $this->actionList = null;
        }

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-property-list-container grouped',
            ),
            array($action_list, $list));
    }

    /**
     * @param array $part
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function renderSectionPart(array $part)
    {
        $name = $part['name'];
        if ($part['icon']) {
            $icon = (new PHUIIconView())
                ->addClass(PHUI::MARGIN_MEDIUM_RIGHT)
                ->setIcon($part['icon'] . ' bluegrey');
            $name = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-property-list-section-header-icon',
                ),
                array($icon, $name));
        }

        return  new PhutilSafeHTML(JavelinHtml::phutil_tag("hr"). JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'mt-2 phui-property-list-section-header',
                ),
                $name));
    }

    /**
     * @param array $part
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    private function renderTextPart(array $part)
    {
        $classes = array();
        $classes[] = 'p-2 phui-property-list-text-content';
        if ($part['type'] == 'image') {
            $classes[] = 'phui-property-list-image-content';
        }
        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode($classes, ' '),
            ),
            $part['content']);
    }

    /**
     * @param array $part
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    private function renderRawPart(array $part)
    {
        $classes = array();
        $classes[] = 'phui-property-list-raw-content';
        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode($classes, ' '),
            ),
            $part['content']);
    }

}
