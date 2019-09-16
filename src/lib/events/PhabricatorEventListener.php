<?php

namespace orangins\lib\events;

use orangins\lib\events\constant\PhabricatorEventType;
use orangins\lib\PhabricatorApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use Yii;
use Exception;

/**
 * Class PhabricatorEventListener
 * @package orangins\lib\events
 * @author 陈妙威
 */
abstract class PhabricatorEventListener extends PhutilEventListener
{
    /**
     * @var
     */
    private $application;

    /**
     * @param PhabricatorApplication $application
     * @return $this
     * @author 陈妙威
     */
    public function setApplication(PhabricatorApplication $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $capability
     * @return bool
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function hasApplicationCapability(
        PhabricatorUser $viewer,
        $capability)
    {
        return PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $this->getApplication(),
            $capability);
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function canUseApplication(PhabricatorUser $viewer)
    {
        return $this->hasApplicationCapability(
            $viewer,
            PhabricatorPolicyCapability::CAN_VIEW);
    }

    /**
     * @param PhutilEvent $event
     * @param $items
     * @throws Exception
     * @author 陈妙威
     */
    protected function addActionMenuItems(PhutilEvent $event, $items)
    {
        if ($event->getType() !== PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS) {
            throw new Exception(Yii::t('app', 'Not an action menu event!'));
        }

        if (!$items) {
            return;
        }

        if (!is_array($items)) {
            $items = array($items);
        }

        $event_actions = $event->getValue('actions');
        foreach ($items as $item) {
            $event_actions[] = $item;
        }
        $event->setValue('actions', $event_actions);
    }
}
