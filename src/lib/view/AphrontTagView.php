<?php

namespace orangins\lib\view;

use orangins\lib\helpers\JavelinHtml;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * View which renders down to a single tag, and provides common access for tag
 * attributes (setting classes, sigils, IDs, etc).
 */
abstract class AphrontTagView extends AphrontView
{

    /**
     * @var
     */
    private $id;
    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var array
     */
    private $sigils = array();
    /**
     * @var
     */
    private $style;
    /**
     * @var
     */
    private $metadata;
    /**
     * @var
     */
    private $mustCapture;
    /**
     * @var
     */
    private $workflow;

    /**
     * @var array
     */
    private $extraTagAttributes = [];

    /**
     * @var int a counter used to generate [[id]] for widgets.
     * @internal
     */
    public static $counter = 0;
    /**
     * @var string the prefix to the automatically generated widget IDs.
     * @see getId()
     */
    public static $autoIdPrefix = 'w';

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
     * @param bool $autoGenerate
     * @return string
     * @author 陈妙威
     */
    public function getID($autoGenerate = true)
    {
        if ($autoGenerate && $this->id === null) {
            $this->id = static::$autoIdPrefix . static::$counter++;
        }
        return $this->id;
    }

    /**
     * @return array
     */
    public function getExtraTagAttributes()
    {
        return $this->extraTagAttributes;
    }

    /**
     * @param array $extraTagAttributes
     * @return self
     */
    public function setExtraTagAttributes($extraTagAttributes)
    {
        $this->extraTagAttributes = $extraTagAttributes;
        return $this;
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
     * @author 陈妙威
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param $must_capture
     * @return $this
     * @author 陈妙威
     */
    public function setMustCapture($must_capture)
    {
        $this->mustCapture = $must_capture;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMustCapture()
    {
        return $this->mustCapture;
    }

    /**
     * @param array $metadata
     * @return $this
     * @author 陈妙威
     */
    final public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param $style
     * @return $this
     * @author 陈妙威
     */
    final public function setStyle($style)
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param $sigil
     * @return $this|AphrontView
     * @author 陈妙威
     */
    final public function addSigil($sigil)
    {
        $this->sigils[] = $sigil;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getSigils()
    {
        return $this->sigils;
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
     * @return array
     * @author 陈妙威
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'div';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        return $this->renderChildren();
    }

    /**
     * @return string|array
     * @throws Exception
     * @author 陈妙威
     */
    final public function render()
    {
        $this->willRender();

        // A tag view may render no tag at all. For example, the HandleListView is
        // a container which renders a tag in HTML mode, but can also render in
        // text mode without producing a tag. When a tag view has no tag name, just
        // return the tag content as though the view did not exist.
        $tag_name = $this->getTagName();
        if ($tag_name === null) {
            return $this->getTagContent();
        }

        $attributes = ArrayHelper::merge($this->getTagAttributes(), $this->getExtraTagAttributes());

        $implode = array('class', 'sigil');
        foreach ($implode as $attr) {
            if (isset($attributes[$attr])) {
                if (is_array($attributes[$attr])) {
                    $attributes[$attr] = implode(' ', $attributes[$attr]);
                }
            }
        }

        if (!is_array($attributes)) {
            $class = get_class($this);
            throw new Exception(
                \Yii::t("app", "View '{0}' did not return an array from getTagAttributes()!", [
                    $class
                ]));
        }

        $sigils = $this->sigils;
        if ($this->workflow) {
            $sigils[] = 'workflow';
        }

        $tag_view_attributes = array(
            'id' => $this->id,

            'class' => implode(' ', $this->classes),
            'style' => $this->style,

            'meta' => $this->metadata,
            'sigil' => $sigils ? implode(' ', $sigils) : null,
            'mustcapture' => $this->mustCapture,
        );

        foreach ($tag_view_attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (!isset($attributes[$key])) {
                $attributes[$key] = $value;
                continue;
            }
            switch ($key) {
                case 'class':
                case 'sigil':
                    $attributes[$key] = $attributes[$key] . ' ' . $value;
                    break;
                default:
                    // Use the explicitly set value rather than the tag default value.
                    $attributes[$key] = $value;
                    break;
            }
        }

        return JavelinHtml::tag($tag_name, $this->getTagContent(), $attributes);
    }
}
