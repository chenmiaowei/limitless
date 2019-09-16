<?php

namespace orangins\modules\transactions\bulk\management;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorBulkManagementMakeSilentWorkflow
 * @package orangins\modules\transactions\bulk\management
 * @author 陈妙威
 */
final class PhabricatorBulkManagementMakeSilentWorkflow
    extends PhabricatorBulkManagementWorkflow
{

    /**
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('make-silent')
            ->setExamples('**make-silent** [options]')
            ->setSynopsis(
                \Yii::t("app", 'Configure a bulk job to execute silently.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'id',
                        'param' => 'id',
                        'help' => \Yii::t("app",
                            'Configure bulk job __id__ to run silently (without sending ' .
                            'mail or publishing notifications).'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $viewer = $this->getViewer();

        $id = $args->getArg('id');
        if (!$id) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'Use "--id" to choose a bulk job to make silent.'));
        }

        $job = PhabricatorWorkerBulkJob::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$job) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Unable to load bulk job with ID "%s".',
                    $id));
        }

        if ($job->getIsSilent()) {
            echo tsprintf(
                "%s\n",
                \Yii::t("app", 'This job is already configured to run silently.'));
            return 0;
        }

        if ($job->getStatus() !== PhabricatorWorkerBulkJob::STATUS_CONFIRM) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Work has already started on job "%s". Jobs can not be ' .
                    'reconfigured after they have been started.',
                    $id));
        }

        $job
            ->setIsSilent(true)
            ->save();

        echo tsprintf(
            "%s\n",
            \Yii::t("app",
                'Configured job "%s" to run silently.',
                $id));

        return 0;
    }

}
