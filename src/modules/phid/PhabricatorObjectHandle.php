<?php

namespace orangins\modules\phid;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\widgets\javelin\JavelinHoverCardAsset;
use Yii;
use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class PhabricatorObjectHandle
 * @package orangins\modules\phid
 * @author 陈妙威
 */
final class PhabricatorObjectHandle
    extends OranginsObject
    implements PhabricatorPolicyInterface
{

    /**
     *
     */
    const AVAILABILITY_FULL = 'full';
    /**
     *
     */
    const AVAILABILITY_NONE = 'none';
    /**
     *
     */
    const AVAILABILITY_NOEMAIL = 'no-email';
    /**
     *
     */
    const AVAILABILITY_PARTIAL = 'partial';
    /**
     *
     */
    const AVAILABILITY_DISABLED = 'disabled';

    /**
     *
     */
    const STATUS_OPEN = 'open';
    /**
     *
     */
    const STATUS_CLOSED = 'closed';

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
    private $type;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $fullName;
    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $imageURI;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $tagColor;
    /**
     * @var
     */
    private $timestamp;
    /**
     * @var string
     */
    private $status = self::STATUS_OPEN;
    /**
     * @var string
     */
    private $availability = self::AVAILABILITY_FULL;
    /**
     * @var
     */
    private $complete;
    /**
     * @var
     */
    private $objectName;
    /**
     * @var
     */
    private $policyFiltered;
    /**
     * @var
     */
    private $subtitle;
    /**
     * @var
     */
    private $tokenIcon;
    /**
     * @var
     */
    private $commandLineObjectName;
    /**
     * @var
     */
    private $mailStampName;

    /**
     * @var
     */
    private $stateIcon;
    /**
     * @var
     */
    private $stateColor;
    /**
     * @var
     */
    private $stateName;

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
     * @return null|string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getIcon()
    {
        if ($this->getPolicyFiltered()) {
            return 'fa-lock';
        }

        if ($this->icon) {
            return $this->icon;
        }
        return $this->getTypeIcon();
    }

    /**
     * @param $subtitle
     * @return $this
     * @author 陈妙威
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setTagColor($color)
    {
        static $colors;
        if (!$colors) {
            $colors = array_fuse(array_keys(PHUITagView::getShadeMap()));
        }

        if (isset($colors[$color])) {
            $this->tagColor = $color;
        }

        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTagColor()
    {
        if ($this->getPolicyFiltered()) {
            return 'disabled';
        }

        if ($this->tagColor) {
            return $this->tagColor;
        }

        return 'blue';
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getIconColor()
    {
        if ($this->tagColor) {
            return $this->tagColor;
        }
        return null;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setTokenIcon($icon)
    {
        $this->tokenIcon = $icon;
        return $this;
    }

    /**
     * @return null|string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTokenIcon()
    {
        if ($this->tokenIcon !== null) {
            return $this->tokenIcon;
        }

        return $this->getIcon();
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTypeIcon()
    {
        if ($this->getPHIDType()) {
            return $this->getPHIDType()->getTypeIcon();
        }
        return null;
    }

    /**
     * @param $policy_filered
     * @return $this
     * @author 陈妙威
     */
    public function setPolicyFiltered($policy_filered)
    {
        $this->policyFiltered = $policy_filered;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPolicyFiltered()
    {
        return $this->policyFiltered;
    }

    /**
     * @param $object_name
     * @return $this
     * @author 陈妙威
     */
    public function setObjectName($object_name)
    {
        $this->objectName = $object_name;
        return $this;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getObjectName()
    {
        if (!$this->objectName) {
            return $this->getName();
        }
        return $this->objectName;
    }

    /**
     * @param $mail_stamp_name
     * @return $this
     * @author 陈妙威
     */
    public function setMailStampName($mail_stamp_name)
    {
        $this->mailStampName = $mail_stamp_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMailStampName()
    {
        return $this->mailStampName;
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
     * @return mixed
     * @author 陈妙威
     */
    public function getURI()
    {
        return $this->uri;
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
     * @return mixed
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->phid;
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
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getName()
    {
        if ($this->name === null) {
            if ($this->getPolicyFiltered()) {
                return Yii::t("app", 'Restricted {0}', [
                    $this->getTypeName()
                ]);
            } else {
                return Yii::t("app", 'Unknown Object ({0})', [
                    $this->getTypeName()
                ]);
            }
        }
        return $this->name;
    }

    /**
     * @param $availability
     * @return $this
     * @author 陈妙威
     */
    public function setAvailability($availability)
    {
        $this->availability = $availability;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDisabled()
    {
        return ($this->getAvailability() == self::AVAILABILITY_DISABLED);
    }

    /**
     * @param $status
     * @return $this
     * @author 陈妙威
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $full_name
     * @return $this
     * @author 陈妙威
     */
    public function setFullName($full_name)
    {
        $this->fullName = $full_name;
        return $this;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getFullName()
    {
        if ($this->fullName !== null) {
            return $this->fullName;
        }
        return $this->getName();
    }

    /**
     * @param $command_line_object_name
     * @return $this
     * @author 陈妙威
     */
    public function setCommandLineObjectName($command_line_object_name)
    {
        $this->commandLineObjectName = $command_line_object_name;
        return $this;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getCommandLineObjectName()
    {
        if ($this->commandLineObjectName !== null) {
            return $this->commandLineObjectName;
        }

        return $this->getObjectName();
    }

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
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return $this->title;
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
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setImageURI($uri)
    {
        $this->imageURI = $uri;
        return $this;
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
     * @param $timestamp
     * @return $this
     * @author 陈妙威
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTypeName()
    {
        if ($this->getPHIDType()) {
            return $this->getPHIDType()->getTypeName();
        }

        return $this->getType();
    }


    /**
     * Set whether or not the underlying object is complete. See
     * @{method:isComplete} for an explanation of what it means to be complete.
     *
     * @param bool True if the handle represents a complete object.
     * @return static
     */
    public function setComplete($complete)
    {
        $this->complete = $complete;
        return $this;
    }


    /**
     * Determine if the handle represents an object which was completely loaded
     * (i.e., the underlying object exists) vs an object which could not be
     * completely loaded (e.g., the type or data for the PHID could not be
     * identified or located).
     *
     * Basically, @{class:PhabricatorHandleQuery} gives you back a handle for
     * any PHID you give it, but it gives you a complete handle only for valid
     * PHIDs.
     *
     * @return bool True if the handle represents a complete object.
     */
    public function isComplete()
    {
        return $this->complete;
    }

    /**
     * @param $state_icon
     * @return $this
     * @author 陈妙威
     */
    public function setStateIcon($state_icon)
    {
        $this->stateIcon = $state_icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStateIcon()
    {
        return $this->stateIcon;
    }

    /**
     * @param $state_color
     * @return $this
     * @author 陈妙威
     */
    public function setStateColor($state_color)
    {
        $this->stateColor = $state_color;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStateColor()
    {
        return $this->stateColor;
    }

    /**
     * @param $state_name
     * @return $this
     * @author 陈妙威
     */
    public function setStateName($state_name)
    {
        $this->stateName = $state_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStateName()
    {
        return $this->stateName;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function renderStateIcon()
    {
        $icon = $this->getStateIcon();
        if ($icon === null) {
            $icon = 'fa-question-circle-o';
        }

        $color = $this->getStateColor();

        $name = $this->getStateName();
        if ($name === null) {
            $name = Yii::t("app", 'Unknown');
        }

        return (new PHUIIconView())
            ->setIcon($icon, $color)
            ->addSigil('has-tooltip')
            ->setMetadata(
                array(
                    'tip' => $name,
                ));
    }

    /**
     * @param null $name
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function renderLink($name = null)
    {
        return $this->renderLinkWithAttributes($name, array());
    }

    /**
     * @param null $name
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function renderHovercardLink($name = null)
    {
        JavelinHtml::initBehavior(new JavelinHoverCardAsset());
        $attributes = array(
            'sigil' => 'hovercard',
            'meta' => array(
                'hoverPHID' => $this->getPHID(),
            ),
        );
        return $this->renderLinkWithAttributes($name, $attributes);
    }

    /**
     * @param $name
     * @param array $attributes
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    private function renderLinkWithAttributes($name, array $attributes)
    {
        if ($name === null) {
            $name = $this->getLinkName();
        }
        $classes = array("text-dark");
        $classes[] = 'phui-handle';
        $title = $this->title;

        if ($this->status != self::STATUS_OPEN) {
            $classes[] = 'handle-status-' . $this->status;
        }

        $circle = null;
        if ($this->availability != self::AVAILABILITY_FULL) {
            $classes[] = 'handle-availability-' . $this->availability;
            $circle = array(
                JavelinHtml::tag("span", "\xE2\x80\xA2", array(
                    'class' => 'text-warning pr-1',
                ))
            );
        }

        if ($this->getType() == PhabricatorPeopleUserPHIDType::TYPECONST) {
            $classes[] = 'phui-link-person';
        }

        $uri = $this->getURI();

        $icon = null;
        if ($this->getPolicyFiltered()) {
            $icon = PHUIIconView::widget(['icon' => "fa-lock lightgreytext"]);
        }

        $attributes = $attributes + array(
                'href' => $uri,
                'class' => implode(' ', $classes),
                'title' => $title,
            );

        return JavelinHtml::tag($uri ? 'a' : 'span', array($circle, $icon, $name), $attributes);
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function renderTag()
    {
        return (new PHUITagView())
            ->setType(PHUITagView::TYPE_SHADE)
            ->setColor($this->getTagColor())
            ->setIcon($this->getIcon())
            ->setHref($this->getURI())
            ->setName($this->getLinkName());
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getLinkName()
    {
        switch ($this->getType()) {
            case PhabricatorPeopleUserPHIDType::TYPECONST:
                $name = $this->getName();
                break;
            default:
                $name = $this->getFullName();
                break;
        }
        return $name;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function getPHIDType()
    {
        $types = PhabricatorPHIDType::getAllTypes();
        return ArrayHelper::getValue($types, $this->getType());
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    /**
     * @param $capability
     * @return mixed
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        // NOTE: Handles are always visible, they just don't get populated with
        // data if the user can't see the underlying object.
        return true;
    }

    /**
     * @param $capability
     * @return null
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return null;
    }

}
