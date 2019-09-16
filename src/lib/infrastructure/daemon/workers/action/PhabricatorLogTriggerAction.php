<?php

namespace orangins\lib\infrastructure\daemon\workers\action;

use orangins\lib\time\PhabricatorTime;
use PhutilTypeSpec;
use Yii;

/**
 * Trivial action which logs a message.
 *
 * This action is primarily useful for testing triggers.
 */
final class PhabricatorLogTriggerAction
    extends PhabricatorTriggerAction
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
                'message' => 'string',
            ));
    }

    /**
     * @param $last_epoch
     * @param $this_epoch
     * @author 陈妙威
     */
    public function execute($last_epoch, $this_epoch)
    {
        $message = \Yii::t("app",
            '({0} -> {1} @ {2}) {3}',
            [
                $last_epoch ? date('Y-m-d g:i:s A', $last_epoch) : 'null',
                date('Y-m-d g:i:s A', $this_epoch),
                date('Y-m-d g:i:s A', PhabricatorTime::getNow()),
                $this->getProperty('message')
            ]);

        Yii::error($message);
    }
}
