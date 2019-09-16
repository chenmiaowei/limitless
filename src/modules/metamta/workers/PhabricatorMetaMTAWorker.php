<?php

namespace orangins\modules\metamta\workers;

use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask;
use orangins\modules\metamta\constants\PhabricatorMailOutboundStatus;
use orangins\modules\metamta\models\exception\PhabricatorMetaMTAPermanentFailureException;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\models\PhabricatorUser;
use yii\helpers\Html;

/**
 * Class PhabricatorMetaMTAWorker
 * @package orangins\modules\metamta\workers
 * @author 陈妙威
 */
final class PhabricatorMetaMTAWorker
    extends PhabricatorWorker
{

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getMaximumRetryCount()
    {
        return 250;
    }

    /**
     * @param PhabricatorWorkerTask $task
     * @return float|int|null
     * @author 陈妙威
     */
    public function getWaitBeforeRetry(PhabricatorWorkerTask $task)
    {
        return ($task->failure_count * 15);
    }

    /**
     * @return mixed|void
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    protected function doWork()
    {
        $message = $this->loadMessage();
        if ($message->getStatus() != PhabricatorMailOutboundStatus::STATUS_QUEUE) {
            return;
        }

        try {
            $message->sendNow();
        } catch (PhabricatorMetaMTAPermanentFailureException $ex) {
            // If the mailer fails permanently, fail this task permanently.
            throw new PhabricatorWorkerPermanentFailureException($ex->getMessage());
        }
    }

    /**
     * @return PhabricatorMetaMTAMail
     * @throws PhabricatorWorkerPermanentFailureException
     * @author 陈妙威
     */
    private function loadMessage()
    {
        $message_id = $this->getTaskData();
        $message = PhabricatorMetaMTAMail::findOne($message_id);

        if (!$message) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app",
                    'Unable to load mail message (with ID "%s") while preparing to ' .
                    'deliver it.',
                    $message_id));
        }

        return $message;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return null|string
     * @author 陈妙威
     */
    public function renderForDisplay(PhabricatorUser $viewer)
    {
        return Html::tag(
            'pre',
            'phabricator/ $ ./bin/mail show-outbound --id ' . $this->getTaskData(),
            array());
    }

}
