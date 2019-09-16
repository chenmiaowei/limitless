<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use PhutilInvalidStateException;
use Exception;

/**
 * Class PHUITabView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUITabView extends AphrontTagView
{

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $keyLocked;
    /**
     * @var
     */
    private $contentID;
    /**
     * @var
     */
    private $color;

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     * @throws Exception
     */
    public function setKey($key)
    {
        if ($this->keyLocked) {
            throw new Exception(
                \Yii::t("app",
                    'Attempting to change the key of a tab with a locked key ("{0}").',
                   [
                       $this->key
                   ]));
        }

        $this->key = $key;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasKey()
    {
        return ($this->key !== null);
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function getKey()
    {
        if (!$this->hasKey()) {
            throw new PhutilInvalidStateException('setKey');
        }

        return $this->key;
    }

    /**
     * @return $this
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function lockKey()
    {
        if (!$this->hasKey()) {
            throw new PhutilInvalidStateException('setKey');
        }

        $this->keyLocked = true;

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
    public function getContentID()
    {
        if ($this->contentID === null) {
            $this->contentID = JavelinHtml::generateUniqueNodeId();
        }

        return $this->contentID;
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
     * @return \orangins\lib\view\AphrontView|PHUIListItemView
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function newMenuItem()
    {
        $item = (new PHUIListItemView())
            ->setName($this->getName())
            ->setKey($this->getKey())
            ->setType(PHUIListItemView::TYPE_LINK)
            ->setHref('#');

        $color = $this->getColor();
        if ($color !== null) {
            $item->setStatusColor($color);
        }

        return $item;
    }

}
