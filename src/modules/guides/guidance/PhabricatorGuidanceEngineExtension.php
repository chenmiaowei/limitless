<?php

namespace orangins\modules\guides\guidance;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorGuidanceEngineExtension
 * @package orangins\modules\guides\guidance
 * @author 陈妙威
 */
abstract class PhabricatorGuidanceEngineExtension extends OranginsObject
{

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getExtensionKey()
    {
        return $this->getPhobjectClassConstant('GUIDANCEKEY', 64);
    }

    /**
     * @param PhabricatorGuidanceContext $context
     * @return mixed
     * @author 陈妙威
     */
    abstract public function canGenerateGuidance(
        PhabricatorGuidanceContext $context);

    /**
     * @param PhabricatorGuidanceContext $context
     * @return mixed
     * @author 陈妙威
     */
    abstract public function generateGuidance(
        PhabricatorGuidanceContext $context);

    /**
     * @param PhabricatorGuidanceContext $context
     * @param array $guidance
     * @return array
     * @author 陈妙威
     */
    public function didGenerateGuidance(
        PhabricatorGuidanceContext $context,
        array $guidance)
    {
        return $guidance;
    }

    /**
     * @param $key
     * @return PhabricatorGuidanceMessage
     * @author 陈妙威
     */
    final protected function newGuidance($key)
    {
        return (new PhabricatorGuidanceMessage())
            ->setKey($key);
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    final protected function newWarning($key)
    {
        return $this->newGuidance($key)
            ->setSeverity(PhabricatorGuidanceMessage::SEVERITY_WARNING);
    }

    /**
     * @return PhabricatorGuidanceEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorGuidanceEngineExtension::class)
            ->setUniqueMethod('getExtensionKey')
            ->execute();
    }

}
