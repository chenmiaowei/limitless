<?php

namespace orangins\modules\config\management;

use orangins\modules\config\models\PhabricatorConfigManualActivity;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorConfigManagementDoneWorkflow
 * @package orangins\modules\config\management
 * @author 陈妙威
 */
final class PhabricatorConfigManagementDoneWorkflow
    extends PhabricatorConfigManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('done')
            ->setExamples('**done** __activity__')
            ->setSynopsis(\Yii::t("app",'Mark a manual upgrade activity as complete.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'force',
                        'short' => 'f',
                        'help' => \Yii::t("app",
                            'Mark activities complete even if there is no outstanding ' .
                            'need to complete them.'),
                    ),
                    array(
                        'name' => 'activities',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $is_force = $args->getArg('force');
        $activities = $args->getArg('activities');
        if (!$activities) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'Specify an activity to mark as completed.'));
        }

        foreach ($activities as $type) {
            $activity = PhabricatorConfigManualActivity::find()
                ->andWhere([
                    'activity_type' => $type
                ])
                ->one();
            if (!$activity) {
                if ($is_force) {
                    echo tsprintf(
                        "%s\n",
                        \Yii::t("app",
                            'Activity "%s" did not need to be marked as complete.',
                            $type));
                } else {
                    throw new PhutilArgumentUsageException(
                        \Yii::t("app",
                            'Activity "%s" is not currently marked as required, so there ' .
                            'is no need to complete it.',
                            $type));
                }
            } else {
                $activity->delete();
                echo tsprintf(
                    "%s\n",
                    \Yii::t("app",
                        'Marked activity "%s" as completed.',
                        $type));
            }
        }

        echo tsprintf(
            "%s\n",
            \Yii::t("app",'Done.'));

        return 0;
    }

}
