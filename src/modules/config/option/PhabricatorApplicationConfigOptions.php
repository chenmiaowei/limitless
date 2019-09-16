<?php

namespace orangins\modules\config\option;

use orangins\lib\request\AphrontRequest;
use PhutilClassMapQuery;
use orangins\modules\config\exception\PhabricatorConfigValidationException;
use Exception;
use orangins\lib\OranginsObject;

/**
 * Class PhabricatorApplicationConfigOptions
 * @package orangins\modules\config\option
 */
abstract class PhabricatorApplicationConfigOptions extends OranginsObject
{

    /**
     * @return mixed
     */
    abstract public function getName();

    /**
     * @return mixed
     */
    abstract public function getDescription();

    /**
     * @return mixed
     */
    abstract public function getGroup();

    /**
     * @return PhabricatorConfigOption[]
     */
    abstract public function getOptions();

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'icon-sliders';
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return void
     * @throws PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     */
    public function validateOption(PhabricatorConfigOption $option, $value)
    {
        if ($value === $option->getDefault()) {
            return;
        }

        if ($value === null) {
            return;
        }

        $type = $option->newOptionType();
        if ($type) {
            try {
                $type->validateStoredValue($option, $value);
                $this->didValidateOption($option, $value);
            } catch (PhabricatorConfigValidationException $ex) {
                throw $ex;
            } catch (Exception $ex) {
                // If custom validators threw exceptions other than validation
                // exceptions, convert them to validation exceptions so we repair the
                // configuration and raise an error.
                throw new PhabricatorConfigValidationException($ex->getMessage());
            }

            return;
        }

        if ($option->isCustomType()) {
            try {
                return $option->newOptionType()->validateOption($option, $value);
            } catch (Exception $ex) {
                throw new PhabricatorConfigValidationException($ex->getMessage());
            }
        } else {
            throw new Exception(
                \Yii::t("app",
                    'Unknown configuration option type "{0}".', [
                        $option->getType()
                    ]));
        }

        $this->didValidateOption($option, $value);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     */
    protected function didValidateOption(
        PhabricatorConfigOption $option,
        $value)
    {
        // Hook for subclasses to do complex validation.
        return;
    }

    /**
     * Hook to render additional hints based on, e.g., the viewing user, request,
     * or other context. For example, this is used to show workspace IDs when
     * configuring `asana.workspace-id`.
     *
     * @param   PhabricatorConfigOption   Option being rendered.
     * @param   AphrontRequest            Active request.
     * @return  wild                      Additional contextual description
     *                                    information.
     */
    public function renderContextualDescription(
        PhabricatorConfigOption $option,
        AphrontRequest $request)
    {
        return null;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        $class = get_class($this);
        $matches = null;
        if (preg_match('/Phabricator(.*)ConfigOptions$/', $class, $matches)) {
            return strtolower($matches[1]);
        }
        return strtolower(get_class($this));
    }

    /**
     * @param $key
     * @param $type
     * @param $default
     * @return PhabricatorConfigOption
     */
    final protected function newOption($key, $type, $default)
    {
        return (new PhabricatorConfigOption())
            ->setKey($key)
            ->setType($type)
            ->setDefault($default)
            ->setGroup($this);
    }

    /**
     * @param bool $external_only
     * @return PhabricatorApplicationConfigOptions[]
     * @throws Exception
     */
    final public static function loadAll($external_only = false)
    {
        $groups = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorApplicationConfigOptions::class)
            ->setUniqueMethod('getKey')
            ->execute();
        return $groups;
    }

    /**
     * @param bool $external_only
     * @return PhabricatorConfigOption[]
     * @throws Exception
     */
    final public static function loadAllOptions($external_only = false)
    {
        $groups = self::loadAll($external_only);
        $options = array();
        foreach ($groups as $group) {
            foreach ($group->getOptions() as $option) {
                $key = $option->getKey();
                if (isset($options[$key])) {
                    throw new Exception(
                        \Yii::t("app",
                            "Multiple {0} subclasses contain an option named '{1}'!",
                            [
                                __CLASS__,
                                $key
                            ]));
                }
                $options[$key] = $option;
            }
        }

        return $options;
    }

    /**
     * Deformat a HEREDOC for use in remarkup by converting line breaks to
     * spaces.
     */
    final protected function deformat($string)
    {
        return preg_replace('/(?<=\S)\n(?=\S)/', ' ', $string);
    }

}
