<?php

namespace orangins\modules\herald\state;

/**
 * Class HeraldCoreStateReasons
 * @package orangins\modules\herald\state
 * @author 陈妙威
 */
final class HeraldCoreStateReasons
    extends HeraldStateReasons
{

    /**
     *
     */
    const REASON_SILENT = 'core.silent';

    /**
     * @param $reason
     * @return mixed|object
     * @author 陈妙威
     */
    public function explainReason($reason)
    {
        $reasons = array(
            self::REASON_SILENT => pht(
                'This change applied silently, so mail and other notifications ' .
                'will not be sent.'),
        );

        return idx($reasons, $reason);
    }

}
