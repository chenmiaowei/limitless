<?php

namespace orangins\modules\metamta\management;

use AphrontQueryException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\metamta\constants\PhabricatorMailOutboundStatus;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use PhutilArgumentParser;
use PhutilArgumentSpecificationException;
use PhutilArgumentUsageException;
use PhutilConsole;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use Throwable;
use yii\db\Exception;
use yii\db\IntegrityException;

/**
 * Class PhabricatorMailManagementResendWorkflow
 * @package orangins\modules\metamta\management
 * @author 陈妙威
 */
final class PhabricatorMailManagementResendWorkflow
    extends PhabricatorMailManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('resend')
            ->setSynopsis(pht('Send mail again.'))
            ->setExamples(
                '**resend** --id 1 --id 2')
            ->setArguments(
                array(
                    array(
                        'name' => 'id',
                        'param' => 'id',
                        'help' => pht('Send mail with a given ID again.'),
                        'repeat' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws PhutilArgumentUsageException
     * @throws AphrontQueryException
     * @throws PhutilArgumentSpecificationException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws Throwable
     * @throws Exception
     * @throws IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $ids = $args->getArg('id');
        if (!$ids) {
            throw new PhutilArgumentUsageException(
                pht(
                    "Use the '%s' flag to specify one or more messages to resend.",
                    '--id'));
        }


        $messages = PhabricatorMetaMTAMail::find()->andWhere(['IN', 'id', $ids])->all();

        if ($ids) {
            $ids = array_fuse($ids);
            $missing = array_diff_key($ids, $messages);
            if ($missing) {
                throw new PhutilArgumentUsageException(
                    pht(
                        'Some specified messages do not exist: %s',
                        implode(', ', array_keys($missing))));
            }
        }

        foreach ($messages as $message) {
            $message->setStatus(PhabricatorMailOutboundStatus::STATUS_QUEUE);
            $message->save();

            $mailer_task = PhabricatorWorker::scheduleTask(
                'PhabricatorMetaMTAWorker',
                $message->getID(),
                array(
                    'priority' => PhabricatorWorker::PRIORITY_ALERTS,
                ));

            $console->writeOut(
                "%s\n",
                pht(
                    'Queued message #%d for resend.',
                    $message->getID()));
        }
    }

}
