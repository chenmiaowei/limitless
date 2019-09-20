<?php

namespace orangins\modules\config\option;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\modules\config\customer\PhabricatorConfigOptionType;
use orangins\modules\people\models\PhabricatorUser;
use \PhutilInvalidStateException;
use orangins\modules\config\type\PhabricatorConfigType;
use orangins\lib\OranginsObject;
use PhutilSafeHTML;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigOption
 * @package orangins\modules\config\option
 */
final class PhabricatorConfigOption extends OranginsObject
{

    /**
     * @var
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
    private $group;
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
    private $baseClass;
    /**
     * @var
     */
    private $customData;
    /**
     * @var
     */
    private $customObject;

    /**
     * @param $base_class
     * @return $this
     * @author 陈妙威
     */
    public function setBaseClass($base_class)
    {
        $this->baseClass = $base_class;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseClass()
    {
        return $this->baseClass;
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
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function getHidden()
    {
        if ($this->hidden) {
            return true;
        }

        return ArrayHelper::getValue(
            PhabricatorEnv::getEnvConfig('config.hide'),
            $this->getKey(),
            false);
    }

    /**
     * @param $locked
     * @return $this
     * @author 陈妙威
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function getLocked()
    {
        if ($this->locked) {
            return true;
        }

        if ($this->getHidden()) {
            return true;
        }

        return ArrayHelper::getValue(
            PhabricatorEnv::getEnvConfig('config.lock'),
            $this->getKey(),
            false);
    }

    /**
     * @param $message
     * @return $this
     * @author 陈妙威
     */
    public function setLockedMessage($message)
    {
        $this->lockedMessage = $message;
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getLockedMessage()
    {
        if ($this->lockedMessage !== null) {
            return $this->lockedMessage;
        }
        return new PhutilSafeHTML(\Yii::t("app",
            'This configuration is locked and can not be edited from the web ' .
            'interface. Use {0} in {1} to edit it.', [
                phutil_tag('tt', array(), './bin/config'),
                phutil_tag('tt', array(), 'phabricator/')
            ]));
    }

    /**
     * @param $value
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function addExample($value, $description)
    {
        $this->examples[] = array($value, $description);
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getExamples()
    {
        return $this->examples;
    }

    /**
     * @param PhabricatorApplicationConfigOptions $group
     * @return $this
     * @author 陈妙威
     */
    public function setGroup(PhabricatorApplicationConfigOptions $group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param array $options
     * @return $this
     * @author 陈妙威
     */
    public function setBoolOptions(array $options)
    {
        $this->boolOptions = $options;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getBoolOptions()
    {
        if ($this->boolOptions) {
            return $this->boolOptions;
        }
        return array(
            \Yii::t("app", 'True'),
            \Yii::t("app", 'False'),
        );
    }

    /**
     * @param array $options
     * @return $this
     * @author 陈妙威
     */
    public function setEnumOptions(array $options)
    {
        $this->enumOptions = $options;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getEnumOptions()
    {
        if ($this->enumOptions) {
            return $this->enumOptions;
        }

        throw new PhutilInvalidStateException('setEnumOptions');
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $default
     * @return $this
     * @author 陈妙威
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param $summary
     * @return $this
     * @author 陈妙威
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSummary()
    {
        if (empty($this->summary)) {
            return $this->getDescription();
        }
        return $this->summary;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return $this->description;
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
     * @return PhabricatorConfigType
     * @author 陈妙威
     */
    public function newOptionType()
    {
        $type_key = $this->getType();
        $type_map = PhabricatorConfigType::getAllTypes();
        return ArrayHelper::getValue($type_map, $type_key);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isCustomType()
    {
        return !strncmp($this->getType(), 'custom:', 7);
    }

    /**
     * @throws Exception
     * @return PhabricatorConfigOptionType
     * @author 陈妙威
     */
    public function getCustomObject()
    {
        if (!$this->customObject) {
            if (!$this->isCustomType()) {
                throw new Exception(\Yii::t("app", 'This option does not have a custom type!'));
            }
            $this->customObject = newv(PhabricatorConfigType::getAllTypes()[substr($this->getType(), 7)], array());
        }
        return $this->customObject;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomData()
    {
        return $this->customData;
    }

    /**
     * @param $data
     * @return $this
     * @author 陈妙威
     */
    public function setCustomData($data)
    {
        $this->customData = $data;
        return $this;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return null|PHUIRemarkupView
     * @author 陈妙威
     */
    public function newDescriptionRemarkupView(PhabricatorUser $viewer)
    {
        $description = $this->getDescription();
        if (!strlen($description)) {
            return null;
        }

        // TODO: Some day, we should probably implement this as a real rule.
        $description = preg_replace(
            '/{{([^}]+)}}/',
            '[[/config/edit/\\1/ | \\1]]',
            $description);

        return new PHUIRemarkupView($viewer, $description);
    }
}
