<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use PhutilSafeHTML;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\AphrontView;
use Exception;

/**
 * Class PhabricatorActionView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class PhabricatorActionView extends AphrontView
{

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $label;
    /**
     * @var
     */
    private $workflow;
    /**
     * @var
     */
    private $renderAsForm;
    /**
     * @var
     */
    private $download;
    /**
     * @var array
     */
    private $sigils = array();
    /**
     * @var
     */
    private $metadata;
    /**
     * @var
     */
    private $selected;
    /**
     * @var
     */
    private $openInNewWindow;
    /**
     * @var array
     */
    private $submenu = array();
    /**
     * @var
     */
    private $hidden;
    /**
     * @var
     */
    private $depth;
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $order;
    /**
     * @var
     */
    private $color;
    /**
     * @var
     */
    private $type;

    /**
     * @var array
     */
    private $classes = [];
    /**
     *
     */
    const TYPE_DIVIDER = 'type-divider';
    /**
     *
     */
    const TYPE_LABEL = 'label';
    /**
     *
     */
    const RED = 'action-item-red';

    /**
     * @param $class
     * @author 陈妙威
     * @return PhabricatorActionView
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param $selected
     * @return $this
     * @author 陈妙威
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * @param $metadata
     * @return $this
     * @author 陈妙威
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param $download
     * @return $this
     * @author 陈妙威
     */
    public function setDownload($download)
    {
        $this->download = $download;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @param $sigil
     * @return $this
     * @author 陈妙威
     */
    public function addSigil($sigil)
    {
        $this->sigils[] = $sigil;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param $workflow
     * @return $this
     * @author 陈妙威
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param $form
     * @return $this
     * @author 陈妙威
     */
    public function setRenderAsForm($form)
    {
        $this->renderAsForm = $form;
        return $this;
    }

    /**
     * @param $open_in_new_window
     * @return $this
     * @author 陈妙威
     */
    public function setOpenInNewWindow($open_in_new_window)
    {
        $this->openInNewWindow = $open_in_new_window;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOpenInNewWindow()
    {
        return $this->openInNewWindow;
    }

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        if (!$this->id) {
            $this->id = JavelinHtml::generateUniqueNodeId();
        }
        return $this->id;
    }

    /**
     * @param $order
     * @return $this
     * @author 陈妙威
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $submenu
     * @return $this
     * @author 陈妙威
     */
    public function setSubmenu(array $submenu)
    {
        $this->submenu = $submenu;

        if (!$this->getHref()) {
            $this->setHref('#');
        }

        return $this;
    }

    /**
     * @param int $depth
     * @return array
     * @author 陈妙威
     */
    public function getItems($depth = 0)
    {
        $items = array();

        $items[] = $this;
        foreach ($this->submenu as $action) {
            foreach ($action->getItems($depth + 1) as $item) {
                $item
                    ->setHidden(true)
                    ->setDepth($depth + 1);

                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param $hidden
     * @return $this
     * @author 陈妙威
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @param $depth
     * @return $this
     * @author 陈妙威
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {


        $caret_id = JavelinHtml::generateUniqueNodeId();

        $icon = null;
        if ($this->icon) {
            $color = '';
            if ($this->disabled) {
                $color = ' grey';
            }
            $icon = (new PHUIIconView())
                ->addClass('phabricator-action-view-icon')
                ->setIcon($this->icon . $color);
        }

        $sigils = array();
        if ($this->workflow) {
            $sigils[] = 'workflow';
        }

        if ($this->download) {
            $sigils[] = 'download';
        }

        if ($this->submenu) {
            $sigils[] = 'keep-open';
        }

        if ($this->sigils) {
            $sigils = array_merge($sigils, $this->sigils);
        }

        $sigils = $sigils ? implode(' ', $sigils) : null;

        if ($this->href) {
            if ($this->renderAsForm) {
                if (!$this->hasViewer()) {
                    throw new Exception(
                        \Yii::t("app",
                            'Call {0} when rendering an action as a form.', [
                                'setViewer()'
                            ]));
                }

                $item = JavelinHtml::phutil_tag('button', array(
                    'class' => 'nav-link p-2 text-grey-800 d-block border-transparent phabricator-action-view-item',
                ), array($icon, $this->name));

                $item = JavelinHtml::phabricator_form(
                    $this->getViewer(),
                    array(
                        'action' => $this->getHref(),
                        'method' => 'POST',
                        'sigil' => $sigils,
                        'class' => 'pl-2',
                        'meta' => $this->metadata,
                    ), $item);
            } else {
                if ($this->getOpenInNewWindow()) {
                    $target = '_blank';
                    $rel = 'noreferrer';
                } else {
                    $target = null;
                    $rel = null;
                }

                if ($this->submenu) {
                    $caret = JavelinHtml::phutil_tag(
                        'span',
                        array(
                            'class' => 'caret-right',
                            'id' => $caret_id,
                        ),
                        '');
                } else {
                    $caret = null;
                }

                $item = JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => $this->getHref(),
                        'class' => 'nav-link text-grey-800 d-block phabricator-action-view-item',
                        'target' => $target,
                        'rel' => $rel,
                        'sigil' => $sigils,
                        'meta' => $this->metadata,
                    ),
                    array($icon, $this->name, $caret));
            }
        } else {
            $item = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'nav-link text-grey-800 d-block phabricator-action-view-item',
                    'sigil' => $sigils,
                ),
                array($icon, $this->name, $this->renderChildren()));
        }

        $classes = array();
        $classes[] = 'nav-item';
        $classes[] = 'phabricator-action-view';

        if ($this->disabled) {
            $classes[] = 'phabricator-action-view-disabled';
        }

        if ($this->label) {
            $classes[] = 'phabricator-action-view-label';
        }

        if ($this->selected) {
            $classes[] = 'phabricator-action-view-selected';
        }

        if ($this->submenu) {
            $classes[] = 'phabricator-action-view-submenu';
        }

        if ($this->getHref()) {
            $classes[] = 'phabricator-action-view-href';
        }

        if ($this->icon) {
            $classes[] = 'action-has-icon';
        }

        if ($this->color) {
            $classes[] = $this->color;
        }

        if ($this->type) {
            $classes[] = 'phabricator-action-view-' . $this->type;
        }
        $classes = array_merge($classes, $this->classes);

        $style = array();

        if ($this->hidden) {
            $style[] = 'display: none;';
        }

        if ($this->depth) {
            $indent = ($this->depth * 16);
            $style[] = "margin-left: {$indent}px;";
        }

        $sigil = null;
        $meta = null;

        if ($this->submenu) {
//            Javelin::initBehavior('phui-submenu');
            $sigil = 'phui-submenu';

            $item_ids = array();
            foreach ($this->submenu as $subitem) {
                $item_ids[] = $subitem->getID();
            }

            $meta = array(
                'itemIDs' => $item_ids,
                'caretID' => $caret_id,
            );
        }

        if ($this->type === self::TYPE_DIVIDER) {
            return JavelinHtml::phutil_tag(
                'li',
                array(
                    'id' => $this->getID(),
                    'class' => implode(' ', $classes),
                    'style' => implode(' ', $style),
                    'sigil' => $sigil,
                    'meta' => $meta,
                ),
                new PhutilSafeHTML('<hr class="m-0"/>'));
        } else {
            return JavelinHtml::phutil_tag(
                'li',
                array(
                    'id' => $this->getID(),
                    'class' => implode(' ', $classes),
                    'style' => implode(' ', $style),
                    'sigil' => $sigil,
                    'meta' => $meta,
                ),
                $item);
        }
    }
}
