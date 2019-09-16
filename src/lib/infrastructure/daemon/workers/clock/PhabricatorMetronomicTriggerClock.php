<?php

namespace orangins\lib\infrastructure\daemon\workers\clock;

use orangins\lib\time\PhabricatorTime;
use PhutilTypeSpec;

/**
 * Triggers an event repeatedly, delaying a fixed number of seconds between
 * triggers.
 *
 * For example, this clock can trigger an event every 30 seconds.
 */
final class PhabricatorMetronomicTriggerClock extends PhabricatorTriggerClock
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
                'period' => 'int',
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
        $period = $this->getProperty('period');

        if ($last_epoch) {
            $next = $last_epoch + $period;
            $next = max($next, $last_epoch + 1);
        } else {
            $next = PhabricatorTime::getNow() + $period;
        }

        return $next;
    }

}
