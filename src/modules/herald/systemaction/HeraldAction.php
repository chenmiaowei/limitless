<?php

namespace orangins\modules\herald\systemaction;

use Exception;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\engine\HeraldEffect;
use orangins\modules\herald\models\HeraldActionRecord;
use orangins\modules\herald\models\transcript\HeraldApplyTranscript;
use orangins\modules\herald\state\HeraldStateReasons;
use orangins\modules\herald\value\HeraldEmptyFieldValue;
use orangins\modules\herald\value\HeraldRemarkupFieldValue;
use orangins\modules\herald\value\HeraldTextFieldValue;
use orangins\modules\herald\value\HeraldTokenizerFieldValue;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Phobject;
use PhutilClassMapQuery;
use PhutilMethodNotImplementedException;

/**
 * Class HeraldAction
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
abstract class HeraldAction extends Phobject
{


    /**
     * @var HeraldAdapter
     */
    private $adapter;
    /**
     * @var
     */
    private $viewer;
    /**
     * @var array
     */
    private $applyLog = array();

    /**
     *
     */
    const STANDARD_NONE = 'standard.none';
    /**
     *
     */
    const STANDARD_PHID_LIST = 'standard.phid.list';
    /**
     *
     */
    const STANDARD_TEXT = 'standard.text';
    /**
     *
     */
    const STANDARD_REMARKUP = 'standard.remarkup';

    /**
     *
     */
    const DO_STANDARD_EMPTY = 'do.standard.empty';
    /**
     *
     */
    const DO_STANDARD_NO_EFFECT = 'do.standard.no-effect';
    /**
     *
     */
    const DO_STANDARD_INVALID = 'do.standard.invalid';
    /**
     *
     */
    const DO_STANDARD_UNLOADABLE = 'do.standard.unloadable';
    /**
     *
     */
    const DO_STANDARD_PERMISSION = 'do.standard.permission';
    /**
     *
     */
    const DO_STANDARD_INVALID_ACTION = 'do.standard.invalid-action';
    /**
     *
     */
    const DO_STANDARD_WRONG_RULE_TYPE = 'do.standard.wrong-rule-type';
    /**
     *
     */
    const DO_STANDARD_FORBIDDEN = 'do.standard.forbidden';

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getHeraldActionName();

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function supportsObject($object);

    /**
     * @param $rule_type
     * @return mixed
     * @author 陈妙威
     */
    abstract public function supportsRuleType($rule_type);

    /**
     * @param $object
     * @param HeraldEffect $effect
     * @return mixed
     * @author 陈妙威
     */
    abstract public function applyEffect($object, HeraldEffect $effect);

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    abstract public function renderActionDescription($value);

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRequiredAdapterStates()
    {
        return array();
    }

    /**
     * @param $type
     * @param $data
     * @return |null
     * @author 陈妙威
     */
    protected function renderActionEffectDescription($type, $data)
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getActionGroupKey()
    {
        return null;
    }

    /**
     * @param $object
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getActionsForObject($object)
    {
        return array($this->getActionConstant() => $this);
    }

    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return |null
     * @author 陈妙威
     */
    protected function getDatasourceValueMap()
    {
        return null;
    }

    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function getHeraldActionStandardType()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return HeraldEmptyFieldValue|HeraldRemarkupFieldValue|HeraldTextFieldValue
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function getHeraldActionValueType()
    {
        switch ($this->getHeraldActionStandardType()) {
            case self::STANDARD_NONE:
                return new HeraldEmptyFieldValue();
            case self::STANDARD_TEXT:
                return new HeraldTextFieldValue();
            case self::STANDARD_REMARKUP:
                return new HeraldRemarkupFieldValue();
            case self::STANDARD_PHID_LIST:
                $tokenizer = (new HeraldTokenizerFieldValue())
                    ->setKey($this->getHeraldActionName())
                    ->setDatasource($this->getDatasource());

                $value_map = $this->getDatasourceValueMap();
                if ($value_map !== null) {
                    $tokenizer->setValueMap($value_map);
                }

                return $tokenizer;
        }

        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $value
     * @return array
     * @author 陈妙威
     */
    public function willSaveActionValue($value)
    {
        try {
            $type = $this->getHeraldActionStandardType();
        } catch (PhutilMethodNotImplementedException $ex) {
            return $value;
        }

        switch ($type) {
            case self::STANDARD_PHID_LIST:
                return array_keys($value);
        }

        return $value;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $target
     * @return array
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function getEditorValue(PhabricatorUser $viewer, $target)
    {
        try {
            $type = $this->getHeraldActionStandardType();
        } catch (PhutilMethodNotImplementedException $ex) {
            return $target;
        }

        switch ($type) {
            case self::STANDARD_PHID_LIST:
                $datasource = $this->getDatasource();

                if (!$datasource) {
                    return array();
                }

                return $datasource
                    ->setViewer($viewer)
                    ->getWireTokens($target);
        }

        return $target;
    }

    /**
     * @param HeraldAdapter $adapter
     * @return $this
     * @author 陈妙威
     */
    final public function setAdapter(HeraldAdapter $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * @return HeraldAdapter
     * @author 陈妙威
     */
    final public function getAdapter()
    {
        return $this->adapter;
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
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    final public function getActionConstant()
    {
        return $this->getPhobjectClassConstant('ACTIONCONST', 64);
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllActions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getActionConstant')
            ->execute();
    }

    /**
     * @param $type
     * @param null $data
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    protected function logEffect($type, $data = null)
    {
        if (!is_string($type)) {
            throw new Exception(
                pht(
                    'Effect type passed to "%s" must be a scalar string.',
                    'logEffect()'));
        }

        $this->applyLog[] = array(
            'type' => $type,
            'data' => $data,
        );

        return $this;
    }

    /**
     * @param HeraldEffect $effect
     * @return HeraldApplyTranscript
     * @author 陈妙威
     */
    final public function getApplyTranscript(HeraldEffect $effect)
    {
        $context = $this->applyLog;
        $this->applyLog = array();
        return new HeraldApplyTranscript($effect, true, $context);
    }

    /**
     * @author 陈妙威
     */
    protected function getActionEffectMap()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $type
     * @return object
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function getActionEffectSpec($type)
    {
        $map = $this->getActionEffectMap() + $this->getStandardEffectMap();
        return idx($map, $type, array());
    }

    /**
     * @param $type
     * @param $data
     * @return object
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    final public function renderActionEffectIcon($type, $data)
    {
        $map = $this->getActionEffectSpec($type);
        return idx($map, 'icon');
    }

    /**
     * @param $type
     * @param $data
     * @return object
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    final public function renderActionEffectColor($type, $data)
    {
        $map = $this->getActionEffectSpec($type);
        return idx($map, 'color');
    }

    /**
     * @param $type
     * @param $data
     * @return object
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    final public function renderActionEffectName($type, $data)
    {
        $map = $this->getActionEffectSpec($type);
        return idx($map, 'name');
    }

    /**
     * @param $phids
     * @return string
     * @author 陈妙威
     */
    protected function renderHandleList($phids)
    {
        if (!is_array($phids)) {
            return pht('(Invalid List)');
        }

        return $this->getViewer()
            ->renderHandleList($phids)
            ->setAsInline(true)
            ->render();
    }

    /**
     * @param array $phids
     * @param array $allowed_types
     * @param array $current_value
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadStandardTargets(
        array $phids,
        array $allowed_types,
        array $current_value)
    {

        $phids = array_fuse($phids);
        if (!$phids) {
            $this->logEffect(self::DO_STANDARD_EMPTY);
        }

        $current_value = array_fuse($current_value);
        $no_effect = array();
        foreach ($phids as $phid) {
            if (isset($current_value[$phid])) {
                $no_effect[] = $phid;
                unset($phids[$phid]);
            }
        }

        if ($no_effect) {
            $this->logEffect(self::DO_STANDARD_NO_EFFECT, $no_effect);
        }

        if (!$phids) {
            return;
        }

        $allowed_types = array_fuse($allowed_types);
        $invalid = array();
        foreach ($phids as $phid) {
            $type = phid_get_type($phid);
            if ($type == PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN) {
                $invalid[] = $phid;
                unset($phids[$phid]);
                continue;
            }

            if ($allowed_types && empty($allowed_types[$type])) {
                $invalid[] = $phid;
                unset($phids[$phid]);
                continue;
            }
        }

        if ($invalid) {
            $this->logEffect(self::DO_STANDARD_INVALID, $invalid);
        }

        if (!$phids) {
            return;
        }

        $targets = (new PhabricatorObjectQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs($phids)
            ->execute();
        $targets = mpull($targets, null, 'getPHID');

        $unloadable = array();
        foreach ($phids as $phid) {
            if (empty($targets[$phid])) {
                $unloadable[] = $phid;
                unset($phids[$phid]);
            }
        }

        if ($unloadable) {
            $this->logEffect(self::DO_STANDARD_UNLOADABLE, $unloadable);
        }

        if (!$phids) {
            return;
        }

        $adapter = $this->getAdapter();
        $object = $adapter->getObject();

        if ($object instanceof PhabricatorPolicyInterface) {
            $no_permission = array();
            foreach ($targets as $phid => $target) {
                if (!($target instanceof PhabricatorUser)) {
                    continue;
                }

                $can_view = PhabricatorPolicyFilter::hasCapability(
                    $target,
                    $object,
                    PhabricatorPolicyCapability::CAN_VIEW);
                if ($can_view) {
                    continue;
                }

                $no_permission[] = $phid;
                unset($targets[$phid]);
            }
        }

        if ($no_permission) {
            $this->logEffect(self::DO_STANDARD_PERMISSION, $no_permission);
        }

        return $targets;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getStandardEffectMap()
    {
        return array(
            self::DO_STANDARD_EMPTY => array(
                'icon' => 'fa-ban',
                'color' => 'grey',
                'name' => pht('No Targets'),
            ),
            self::DO_STANDARD_NO_EFFECT => array(
                'icon' => 'fa-circle-o',
                'color' => 'grey',
                'name' => pht('No Effect'),
            ),
            self::DO_STANDARD_INVALID => array(
                'icon' => 'fa-ban',
                'color' => 'red',
                'name' => pht('Invalid Targets'),
            ),
            self::DO_STANDARD_UNLOADABLE => array(
                'icon' => 'fa-ban',
                'color' => 'red',
                'name' => pht('Unloadable Targets'),
            ),
            self::DO_STANDARD_PERMISSION => array(
                'icon' => 'fa-lock',
                'color' => 'red',
                'name' => pht('No Permission'),
            ),
            self::DO_STANDARD_INVALID_ACTION => array(
                'icon' => 'fa-ban',
                'color' => 'red',
                'name' => pht('Invalid Action'),
            ),
            self::DO_STANDARD_WRONG_RULE_TYPE => array(
                'icon' => 'fa-ban',
                'color' => 'red',
                'name' => pht('Wrong Rule Type'),
            ),
            self::DO_STANDARD_FORBIDDEN => array(
                'icon' => 'fa-ban',
                'color' => 'violet',
                'name' => pht('Forbidden'),
            ),
        );
    }

    /**
     * @param $type
     * @param $data
     * @return string|null
     * @author 陈妙威
     */
    final public function renderEffectDescription($type, $data)
    {
        $result = $this->renderActionEffectDescription($type, $data);
        if ($result !== null) {
            return $result;
        }

        switch ($type) {
            case self::DO_STANDARD_EMPTY:
                return pht(
                    'This action specifies no targets.');
            case self::DO_STANDARD_NO_EFFECT:
                if ($data && is_array($data)) {
                    return pht(
                        'This action has no effect on %s target(s): %s.',
                        phutil_count($data),
                        $this->renderHandleList($data));
                } else {
                    return pht('This action has no effect.');
                }
            case self::DO_STANDARD_INVALID:
                return pht(
                    '%s target(s) are invalid or of the wrong type: %s.',
                    phutil_count($data),
                    $this->renderHandleList($data));
            case self::DO_STANDARD_UNLOADABLE:
                return pht(
                    '%s target(s) could not be loaded: %s.',
                    phutil_count($data),
                    $this->renderHandleList($data));
            case self::DO_STANDARD_PERMISSION:
                return pht(
                    '%s target(s) do not have permission to see this object: %s.',
                    phutil_count($data),
                    $this->renderHandleList($data));
            case self::DO_STANDARD_INVALID_ACTION:
                return pht(
                    'No implementation is available for rule "%s".',
                    $data);
            case self::DO_STANDARD_WRONG_RULE_TYPE:
                return pht(
                    'This action does not support rules of type "%s".',
                    $data);
            case self::DO_STANDARD_FORBIDDEN:
                return HeraldStateReasons::getExplanation($data);
        }

        return null;
    }

    /**
     * @param HeraldActionRecord $record
     * @return array
     * @author 陈妙威
     */
    public function getPHIDsAffectedByAction(HeraldActionRecord $record)
    {
        return array();
    }

}
