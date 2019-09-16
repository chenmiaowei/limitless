<?php

namespace orangins\lib\infrastructure\daemon\workers\clock;

use PhutilTypeSpec;

/**
 * Triggers an event exactly once, at a specific epoch time.
 */
final class PhabricatorOneTimeTriggerClock
    extends PhabricatorTriggerClock
{

    /**
     * @param array $properties
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @author 陈妙威
     */
    public function validateProperties(array $properties)
    {
        PhutilTypeSpec::checkMap(
            $properties,
            array(
                'epoch' => 'int',
            ));
    }

    /**
     * @param $last_epoch
     * @param $is_reschedule
     * @return int|mixed|null
     * @author 陈妙威
     */
    public function getNextEventEpoch($last_epoch, $is_reschedule)
    {
        if ($last_epoch) {
            return null;
        }

        return $this->getProperty('epoch');
    }

}
