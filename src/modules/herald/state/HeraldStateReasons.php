<?php

namespace orangins\modules\herald\state;

use Phobject;
use PhutilClassMapQuery;
use PhutilInvalidStateException;

/**
 * Class HeraldStateReasons
 * @package orangins\modules\herald\state
 * @author 陈妙威
 */
abstract class HeraldStateReasons extends Phobject
{

    /**
     * @param $reason
     * @return mixed
     * @author 陈妙威
     */
    abstract public function explainReason($reason);

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllReasons()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->execute();
    }

    /**
     * @param $reason
     * @return string
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getExplanation($reason)
    {
        $reasons = self::getAllReasons();

        foreach ($reasons as $reason_implementation) {
            $explanation = $reason_implementation->explainReason($reason);
            if ($explanation !== null) {
                return $explanation;
            }
        }

        return pht('Unknown reason ("%s").', $reason);
    }

}
