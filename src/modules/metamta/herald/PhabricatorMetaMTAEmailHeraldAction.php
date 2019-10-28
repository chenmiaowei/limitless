<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\state\HeraldMailableState;
use orangins\modules\herald\systemaction\HeraldAction;
use orangins\modules\herald\systemaction\HeraldNotifyActionGroup;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;

/**
 * Class PhabricatorMetaMTAEmailHeraldAction
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
abstract class PhabricatorMetaMTAEmailHeraldAction
    extends HeraldAction
{

    /**
     *
     */
    const DO_SEND = 'do.send';
    /**
     *
     */
    const DO_FORCE = 'do.force';

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRequiredAdapterStates()
    {
        return array(
            HeraldMailableState::STATECONST,
        );
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return self::isMailGeneratingObject($object);
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public static function isMailGeneratingObject($object)
    {
        // NOTE: This implementation lacks generality, but there's no great way to
        // figure out if something generates email right now.

//        if ($object instanceof DifferentialDiff) {
//            return false;
//        }

        if ($object instanceof PhabricatorMetaMTAMail) {
            return false;
        }

        return true;
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getActionGroupKey()
    {
        return HeraldNotifyActionGroup::ACTIONGROUPKEY;
    }

    /**
     * @param array $phids
     * @param $force
     * @throws \Exception
     * @author 陈妙威
     */
    protected function applyEmail(array $phids, $force)
    {
        $adapter = $this->getAdapter();

        $allowed_types = array(
            PhabricatorPeopleUserPHIDType::TYPECONST,
//            PhabricatorProjectProjectPHIDType::TYPECONST,
        );

        // There's no stateful behavior for this action: we always just send an
        // email.
        $current = array();

        $targets = $this->loadStandardTargets($phids, $allowed_types, $current);
        if (!$targets) {
            return;
        }

        $phids = array_fuse(array_keys($targets));

        foreach ($phids as $phid) {
            $adapter->addEmailPHID($phid, $force);
        }

        if ($force) {
            $this->logEffect(self::DO_FORCE, $phids);
        } else {
            $this->logEffect(self::DO_SEND, $phids);
        }
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function getActionEffectMap()
    {
        return array(
            self::DO_SEND => array(
                'icon' => 'fa-envelope',
                'color' => 'green',
                'name' => pht('Sent Mail'),
            ),
            self::DO_FORCE => array(
                'icon' => 'fa-envelope',
                'color' => 'blue',
                'name' => pht('Forced Mail'),
            ),
        );
    }

    /**
     * @param $type
     * @param $data
     * @return string|null
     * @author 陈妙威
     */
    protected function renderActionEffectDescription($type, $data)
    {
        switch ($type) {
            case self::DO_SEND:
                return pht(
                    'Queued email to be delivered to %s target(s): %s.',
                    phutil_count($data),
                    $this->renderHandleList($data));
            case self::DO_FORCE:
                return pht(
                    'Queued email to be delivered to %s target(s), ignoring their ' .
                    'notification preferences: %s.',
                    phutil_count($data),
                    $this->renderHandleList($data));
        }
    }

}
