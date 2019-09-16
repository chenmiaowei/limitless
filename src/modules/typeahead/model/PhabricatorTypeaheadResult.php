<?php

namespace orangins\modules\typeahead\model;

use orangins\lib\OranginsObject;
use orangins\lib\helpers\OranginsUtf8;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDType;

/**
 * Class PhabricatorTypeaheadResult
 * @package orangins\modules\typeahead\model
 * @author 陈妙威
 */
final class PhabricatorTypeaheadResult extends OranginsObject
{

    const TYPE_OBJECT = 'object';
    const TYPE_DISABLED = 'disabled';
    const TYPE_FUNCTION = 'function';
    const TYPE_INVALID = 'invalid';

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $uri;
    /**
     * @var
     */
    private $phid;
    /**
     * @var
     */
    private $priorityString;
    /**
     * @var
     */
    private $displayName;
    /**
     * @var
     */
    private $displayType;
    /**
     * @var
     */
    private $imageURI;
    /**
     * @var
     */
    private $priorityType;
    /**
     * @var
     */
    private $imageSprite;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $color;
    /**
     * @var
     */
    private $closed;
    /**
     * @var
     */
    private $tokenType;
    /**
     * @var
     */
    private $unique;
    /**
     * @var
     */
    private $autocomplete;
    /**
     * @var array
     */
    private $attributes = array();
    /**
     * @var
     */
    private $phase;

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
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setURI($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }

    /**
     * @param $priority_string
     * @return $this
     * @author 陈妙威
     */
    public function setPriorityString($priority_string)
    {
        $this->priorityString = $priority_string;
        return $this;
    }

    /**
     * @param $display_name
     * @return $this
     * @author 陈妙威
     */
    public function setDisplayName($display_name)
    {
        $this->displayName = $display_name;
        return $this;
    }

    /**
     * @param $display_type
     * @return $this
     * @author 陈妙威
     */
    public function setDisplayType($display_type)
    {
        $this->displayType = $display_type;
        return $this;
    }

    /**
     * @param $image_uri
     * @return $this
     * @author 陈妙威
     */
    public function setImageURI($image_uri)
    {
        $this->imageURI = $image_uri;
        return $this;
    }

    /**
     * @param $priority_type
     * @return $this
     * @author 陈妙威
     */
    public function setPriorityType($priority_type)
    {
        $this->priorityType = $priority_type;
        return $this;
    }

    /**
     * @param $image_sprite
     * @return $this
     * @author 陈妙威
     */
    public function setImageSprite($image_sprite)
    {
        $this->imageSprite = $image_sprite;
        return $this;
    }

    /**
     * @param $closed
     * @return $this
     * @author 陈妙威
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;
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
     * @return mixed
     * @author 陈妙威
     */
    public function getDisplayName()
    {
        return OranginsUtil::coalesce($this->displayName, $this->getName());
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getIcon()
    {
        return OranginsUtil::nonempty($this->icon, $this->getDefaultIcon());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->phid;
    }

    /**
     * @param $unique
     * @return $this
     * @author 陈妙威
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setTokenType($type)
    {
        $this->tokenType = $type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTokenType()
    {
        if ($this->closed && !$this->tokenType) {
            return self::TYPE_DISABLED;
        }
        return $this->tokenType;
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
     * @return mixed
     * @author 陈妙威
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param $autocomplete
     * @return $this
     * @author 陈妙威
     */
    public function setAutocomplete($autocomplete)
    {
        $this->autocomplete = $autocomplete;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAutocomplete()
    {
        return $this->autocomplete;
    }

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getSortKey()
    {
        // Put unique results (special parameter functions) ahead of other
        // results.
        if ($this->unique) {
            $prefix = 'A';
        } else {
            $prefix = 'B';
        }

        return $prefix . OranginsUtf8::phutil_utf8_strtolower($this->getName());
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getWireFormat()
    {
        $data = array(
            $this->name,
            $this->uri ? (string)$this->uri : null,
            $this->phid,
            $this->priorityString,
            $this->displayName,
            $this->displayType,
            $this->imageURI ? (string)$this->imageURI : null,
            $this->priorityType,
            $this->getIcon(),
            $this->closed,
            $this->imageSprite ? (string)$this->imageSprite : null,
            $this->color,
            $this->tokenType,
            $this->unique ? 1 : null,
            $this->autocomplete,
            $this->phase,
        );
        while (end($data) === null) {
            array_pop($data);
        }
        return $data;
    }

    /**
     * If the datasource did not specify an icon explicitly, try to select a
     * default based on PHID type.
     * @return mixed|null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function getDefaultIcon()
    {
        static $icon_map;
        if ($icon_map === null) {
            $types = PhabricatorPHIDType::getAllTypes();

            $map = array();
            foreach ($types as $type) {
                $icon = $type->getTypeIcon();
                if ($icon !== null) {
                    $map[$type->getTypeConstant()] = $icon;
                }
            }

            $icon_map = $map;
        }

        $phid_type = PhabricatorPHID::phid_get_type($this->phid);
        if (isset($icon_map[$phid_type])) {
            return $icon_map[$phid_type];
        }

        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getImageURI()
    {
        return $this->imageURI;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getClosed()
    {
        return $this->closed;
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function resetAttributes()
    {
        $this->attributes = array();
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param $attribute
     * @return $this
     * @author 陈妙威
     */
    public function addAttribute($attribute)
    {
        $this->attributes[] = $attribute;
        return $this;
    }

    /**
     * @param $phase
     * @return $this
     * @author 陈妙威
     */
    public function setPhase($phase)
    {
        $this->phase = $phase;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPhase()
    {
        return $this->phase;
    }

}
