<?php

namespace orangins\modules\policy\codex;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use orangins\modules\policy\rule\PhabricatorPolicyRule;
use Exception;

/**
 * Rendering extensions that allows an object to render custom strings,
 * descriptions and explanations for the policy system to help users
 * understand complex policies.
 */
abstract class PhabricatorPolicyCodex
    extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $policy;
    /**
     * @var
     */
    private $capability;

    /**
     * @return null
     * @author 陈妙威
     */
    public function getPolicyShortName()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getPolicyIcon()
    {
        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPolicyTagClasses()
    {
        return array();
    }

    /**
     * @return PhabricatorPolicyCodexRuleDescription[]
     * @author 陈妙威
     */
    public function getPolicySpecialRuleDescriptions()
    {
        return array();
    }

    /**
     * @return PhabricatorPolicy
     * @throws Exception
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getDefaultPolicy()
    {
        return PhabricatorPolicyQuery::getDefaultPolicyForObject(
            $this->viewer,
            $this->object,
            $this->capability);
    }

    /**
     * @param PhabricatorPolicy $policy
     * @return null
     * @author 陈妙威
     */
    public function compareToDefaultPolicy(PhabricatorPolicy $policy)
    {
        return null;
    }

    /**
     * @param $capability
     * @return mixed|null
     * @author 陈妙威
     */
    final public function getPolicySpecialRuleForCapability($capability)
    {
        foreach ($this->getPolicySpecialRuleDescriptions() as $rule) {
            if (in_array($capability, $rule->getCapabilities())) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @return PhabricatorPolicyCodexRuleDescription
     * @author 陈妙威
     */
    final protected function newRule()
    {
        return new PhabricatorPolicyCodexRuleDescription();
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorPolicyCodexInterface $object
     * @return $this
     * @author 陈妙威
     */
    final public function setObject(PhabricatorPolicyCodexInterface $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getObject()
    {
        return $this->object;
    }

    /**
     * @param $capability
     * @return $this
     * @author 陈妙威
     */
    final public function setCapability($capability)
    {
        $this->capability = $capability;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getCapability()
    {
        return $this->capability;
    }

    /**
     * @param PhabricatorPolicy $policy
     * @return $this
     * @author 陈妙威
     */
    final public function setPolicy(PhabricatorPolicy $policy)
    {
        $this->policy = $policy;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * @param PhabricatorPolicyCodexInterface $object
     * @param PhabricatorUser $viewer
     * @return PhabricatorPolicyCodex
     * @throws Exception
     * @author 陈妙威
     */
    final public static function newFromObject(
        PhabricatorPolicyCodexInterface $object,
        PhabricatorUser $viewer)
    {

        if (!($object instanceof PhabricatorPolicyInterface)) {
            throw new Exception(
                \Yii::t("app",
                    'Object (of class "%s") implements interface "%s", but must also ' .
                    'implement interface "%s".',
                    get_class($object),
                    'PhabricatorPolicyCodexInterface',
                    'PhabricatorPolicyInterface'));
        }

        $codex = $object->newPolicyCodex();
        if (!($codex instanceof PhabricatorPolicyCodex)) {
            throw new Exception(
                \Yii::t("app",
                    'Object (of class "%s") implements interface "%s", but defines ' .
                    'method "%s" incorrectly: this method must return an object of ' .
                    'class "%s".',
                    get_class($object),
                    'PhabricatorPolicyCodexInterface',
                    'newPolicyCodex()',
                    __CLASS__));
        }

        $codex
            ->setObject($object)
            ->setViewer($viewer);

        return $codex;
    }

}
