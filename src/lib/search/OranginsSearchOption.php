<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/25
 * Time: 9:50 PM
 */

namespace orangins\lib\search;

use orangins\modules\config\exception\PhabricatorConfigValidationException;
use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

class OranginsSearchOption extends OranginsObject
{
    public $customObject;
    /**
     * @var string
     */
    private $key;
    /**
     * @var
     */
    private $default;
    /**
     * @var
     */
    private $summary;
    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $boolOptions;
    /**
     * @var
     */
    private $enumOptions;

    /**
     * @var
     */
    private $examples;
    /**
     * @var
     */
    private $locked;
    /**
     * @var
     */
    private $lockedMessage;
    /**
     * @var
     */
    private $hidden;
    /**
     * @var
     */
    private $customData;

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     * @return OranginsSearchOption
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     * @return OranginsSearchOption
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param mixed $summary
     * @return OranginsSearchOption
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return OranginsSearchOption
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return OranginsSearchOption
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBoolOptions()
    {
        return $this->boolOptions;
    }

    /**
     * @param mixed $boolOptions
     * @return OranginsSearchOption
     */
    public function setBoolOptions($boolOptions)
    {
        $this->boolOptions = $boolOptions;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEnumOptions()
    {
        return $this->enumOptions;
    }

    /**
     * @param mixed $enumOptions
     * @return OranginsSearchOption
     */
    public function setEnumOptions($enumOptions)
    {
        $this->enumOptions = $enumOptions;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getExamples()
    {
        return $this->examples;
    }

    /**
     * @param mixed $examples
     * @return OranginsSearchOption
     */
    public function setExamples($examples)
    {
        $this->examples = $examples;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * @param mixed $locked
     * @return OranginsSearchOption
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLockedMessage()
    {
        return $this->lockedMessage;
    }

    /**
     * @param mixed $lockedMessage
     * @return OranginsSearchOption
     */
    public function setLockedMessage($lockedMessage)
    {
        $this->lockedMessage = $lockedMessage;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @param mixed $hidden
     * @return OranginsSearchOption
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCustomData()
    {
        return $this->customData;
    }

    /**
     * @param mixed $customData
     * @return OranginsSearchOption
     */
    public function setCustomData($customData)
    {
        $this->customData = $customData;
        return $this;
    }

    /**
     * @return OranginsSearchType
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function newOptionType()
    {
        $typeMap = OranginsSearchType::getAllTypes();
        $optionType = ArrayHelper::getValue($typeMap, $this->getType());

        if ($optionType) {
            /** @var OranginsSearchType $object */
            $object = \Yii::createObject([
                "class" => $optionType,
            ]);;
            return $object;
        } else {
            if (!$this->customObject) {
                if (class_exists($this->getType())) {
                    /** @var OranginsSearchType $object */
                    $object = \Yii::createObject([
                        "class" => $this->getType(),
                    ]);
                    $this->customObject = $object;
                } else {
                    throw new PhabricatorConfigValidationException(\Yii::t("app", "class {0} is not exist.", [$this->getType()]));
                }
            }
            return $this->customObject;
        }
    }
}