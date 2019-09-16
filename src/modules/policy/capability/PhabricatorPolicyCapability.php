<?php

namespace orangins\modules\policy\capability;


use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPolicyCapability
 * @package orangins\modules\policy\capability
 * @author 陈妙威
 */
abstract class PhabricatorPolicyCapability extends OranginsObject
{


    /**
     *
     */
    const CAN_VIEW = 'view';
    /**
     *
     */
    const CAN_EDIT = 'edit';
    /**
     *
     */
    const CAN_JOIN = 'join';
    /**
     *
     */
    const CAN_INTERACT = 'interact';

    /**
     *
     */
    const GROUP_SYSTEM = 'SYSTEM';

    /**
     * Get the unique key identifying this capability. This key must be globally
     * unique. Application capabilities should be namespaced. For example:
     *
     *   application.create
     *
     * @return string Globally unique capability key.
     * @throws \ReflectionException
     */
    final public function getCapabilityKey()
    {
        return $this->getPhobjectClassConstant('CAPABILITY');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getGroup()
    {
        return self::GROUP_SYSTEM;
    }

    /**
     * Return a human-readable descriptive name for this capability, like
     * "Can View".
     *
     * @return string Human-readable name describing the capability.
     */
    abstract public function getCapabilityName();


    /**
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    abstract public function getApplicationClassName();


    /**
     * Return a human-readable string describing what not having this capability
     * prevents the user from doing. For example:
     *
     *   - You do not have permission to edit this object.
     *   - You do not have permission to create new tasks.
     *
     * @return string Human-readable name describing what failing a check for this
     *   capability prevents the user from doing.
     */
    public function describeCapabilityRejection()
    {
        return null;
    }

    /**
     * Can this capability be set to "public"? Broadly, this is only appropriate
     * for view and view-related policies.
     *
     * @return bool True to allow the "public" policy. Returns false by default.
     */
    public function shouldAllowPublicPolicySetting()
    {
        return false;
    }

    /**
     * @param $key
     * @return PhabricatorPolicyCapability
     * @author 陈妙威
     */
    final public static function getCapabilityByKey($key)
    {
        $capabilityMap = self::getCapabilityMap();
        return ArrayHelper::getValue($capabilityMap, $key);
    }

    /**
     * @param string $group
     * @return PhabricatorPolicyCapability[]
     * @author 陈妙威
     */
    final public static function getCapabilityMap($group = self::GROUP_SYSTEM)
    {
        /** @var PhabricatorPolicyCapability[] $execute */
        $execute = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorPolicyCapability::class)
            ->setUniqueMethod('getCapabilityKey')
            ->execute();

        foreach ($execute as $k => $item) {
            if ($item->getGroup() !== $group) {
                unset($execute[$k]);
            }
        }
        return $execute;
    }
}
