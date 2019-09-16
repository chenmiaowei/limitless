<?php

namespace orangins\modules\config\type;

use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\config\option\PhabricatorConfigOption;
use PhutilSymbolLoader;
use Exception;

/**
 * Class PhabricatorClassConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorClassConfigType
    extends PhabricatorTextConfigType
{

    /**
     *
     */
    const TYPEKEY = 'class';

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed|void
     * @throws \ReflectionException
     * @throws \orangins\modules\config\exception\PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function validateStoredValue(
        PhabricatorConfigOption $option,
        $value)
    {

        if (!is_string($value)) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s", but the configured value is not ' .
                    'a string.',
                    $option->getKey(),
                    $this->getTypeKey()));
        }

        $base = $option->getBaseClass();
        $map = $this->getClassOptions($option);

        try {
            $ok = class_exists($value);
        } catch (Exception $ex) {
            $ok = false;
        }

        if (!$ok) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s", but the configured value is not the ' .
                    'name of a known class. Valid selections are: %s.',
                    $option->getKey(),
                    $this->getTypeKey(),
                    implode(', ', array_keys($map))));
        }

        if (!isset($map[$value])) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s", but the current value ("%s") is not ' .
                    'a known, concrete subclass of base class "%s". Valid selections ' .
                    'are: %s.',
                    $option->getKey(),
                    $this->getTypeKey(),
                    $value,
                    $base,
                    implode(', ', array_keys($map))));
        }
    }

    /**
     * @param PhabricatorConfigOption $option
     * @return \orangins\lib\view\form\control\AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl(PhabricatorConfigOption $option)
    {
        $map = array(
                '' => \Yii::t("app",'(Use Default)'),
            ) + $this->getClassOptions($option);

        return (new AphrontFormSelectControl())
            ->setOptions($map);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @return \dict
     * @author 陈妙威
     */
    private function getClassOptions(PhabricatorConfigOption $option)
    {
        $symbols = (new PhutilSymbolLoader())
            ->setType('class')
            ->setAncestorClass($option->getBaseClass())
            ->setConcreteOnly(true)
            ->selectSymbolsWithoutLoading();

        $map = ipull($symbols, 'name', 'name');
        asort($map);

        return $map;
    }

}
