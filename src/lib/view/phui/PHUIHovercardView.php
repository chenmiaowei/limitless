<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 10:49 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use PhutilInvalidStateException;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\spaces\view\PHUISpacesNamespaceContextView;
use PhutilSafeHTML;
use ReflectionException;

/**
 * Class PHUIHovercardView
 * @package orangins\modules\widgets\components
 * @author 陈妙威
 */
class PHUIHovercardView extends AphrontTagView {

    /**
     * @var PhabricatorObjectHandle
     */
    private $handle;
    /**
     * @var
     */
    private $object;

    /**
     * @var array
     */
    private $title = array();
    /**
     * @var
     */
    private $detail;
    /**
     * @var array
     */
    private $tags = array();
    /**
     * @var array
     */
    private $fields = array();
    /**
     * @var array
     */
    private $actions = array();
    /**
     * @var array
     */
    private $badges = array();

    /**
     * @param PhabricatorObjectHandle $handle
     * @return $this
     * @author 陈妙威
     */
    public function setObjectHandle(PhabricatorObjectHandle $handle) {
        $this->handle = $handle;
        return $this;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object) {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $detail
     * @return $this
     * @author 陈妙威
     */
    public function setDetail($detail) {
        $this->detail = $detail;
        return $this;
    }

    /**
     * @param $label
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function addField($label, $value) {
        $this->fields[] = array(
            'label' => $label,
            'value' => $value,
        );
        return $this;
    }

    /**
     * @param $label
     * @param $uri
     * @param bool $workflow
     * @return $this
     * @author 陈妙威
     */
    public function addAction($label, $uri, $workflow = false) {
        $this->actions[] = array(
            'label'    => $label,
            'uri'      => $uri,
            'workflow' => $workflow,
        );
        return $this;
    }

    /**
     * @param PHUITagView $tag
     * @return $this
     * @author 陈妙威
     */
    public function addTag(PHUITagView $tag) {
        $this->tags[] = $tag;
        return $this;
    }

    /**
     * @param PHUIBadgeMiniView $badge
     * @return $this
     * @author 陈妙威
     */
    public function addBadge(PHUIBadgeMiniView $badge) {
        $this->badges[] = $badge;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes() {
        $classes = array();
        $classes[] = 'phui-hovercard-wrapper';

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array|PhutilSafeHTML
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent() {
        if (!$this->handle) {
            throw new PhutilInvalidStateException('setObjectHandle');
        }

        $viewer = $this->getUser();
        $handle = $this->handle;

        // If we're a fully custom Hovercard, skip the common UI
        $children = $this->renderChildren();
        if ($children) {
            return $children;
        }

        $title = array(
            (new PHUISpacesNamespaceContextView())
                ->setUser($viewer)
                ->setObject($this->getObject()),
            $this->title ? $this->title : $handle->getName(),
        );

        $header = new PHUIHeaderView();
        $header->setHeader($title);
        if ($this->tags) {
            foreach ($this->tags as $tag) {
                $header->addActionItem($tag);
            }
        }

        $body = array();

        $body_title = null;
        if ($this->detail) {
            $body_title = $this->detail;
        } else if (!$this->fields) {
            // Fallback for object handles
            $body_title = $handle->getFullName();
        }

        if ($body_title) {
            $body[] = phutil_tag_div('phui-hovercard-body-header', $body_title);
        }

        foreach ($this->fields as $field) {
            $item = array(
                phutil_tag('strong', array(), $field['label']),
                ': ',
                phutil_tag('span', array(), $field['value']),
            );
            $body[] = phutil_tag_div('phui-hovercard-body-item', $item);
        }

        if ($this->badges) {
            $badges = (new PHUIBadgeBoxView())
                ->addItems($this->badges)
                ->setCollapsed(true);
            $body[] = phutil_tag(
                'div',
                array(
                    'class' => 'phui-hovercard-body-item hovercard-badges',
                ),
                $badges);
        }

        if ($handle->getImageURI()) {
            // Probably a user, we don't need to assume something else
            // "Prepend" the image by appending $body
            $body = phutil_tag(
                'div',
                array(
                    'class' => 'phui-hovercard-body-image',
                ),
                phutil_tag(
                    'div',
                    array(
                        'class' => 'profile-header-picture-frame',
                        'style' => 'background-image: url('.$handle->getImageURI().');',
                    ),
                    ''))
                ->appendHTML(
                    phutil_tag(
                        'div',
                        array(
                            'class' => 'phui-hovercard-body-details',
                        ),
                        $body));
        }

        $buttons = array();

        foreach ($this->actions as $action) {
            $options = array(
                'class' => 'button button-grey',
                'href'  => $action['uri'],
            );

            if ($action['workflow']) {
                $options['sigil'] = 'workflow';
                $buttons[] = JavelinHtml::phutil_tag(
                    'a',
                    $options,
                    $action['label']);
            } else {
                $buttons[] = phutil_tag(
                    'a',
                    $options,
                    $action['label']);
            }
        }

        $tail = null;
        if ($buttons) {
            $tail = phutil_tag_div('phui-hovercard-tail', $buttons);
        }

        $hovercard = phutil_tag_div(
            'phui-hovercard-container grouped',
            array(
                phutil_tag_div('phui-hovercard-head', $header),
                phutil_tag_div('phui-hovercard-body grouped', $body),
                $tail,
            ));

        return $hovercard;
    }

}
