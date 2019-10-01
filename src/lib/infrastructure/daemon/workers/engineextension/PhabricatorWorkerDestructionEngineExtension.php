<?php

namespace orangins\lib\infrastructure\daemon\workers\engineextension;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\engine\PhabricatorDestructionEngineExtension;
use Throwable;
use Yii;

/**
 * Class PhabricatorWorkerDestructionEngineExtension
 * @package orangins\lib\infrastructure\daemon\workers\engineextension
 * @author 陈妙威
 */
final class PhabricatorWorkerDestructionEngineExtension
    extends PhabricatorDestructionEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'workers';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return Yii::t("app",'Worker Tasks');
    }

    /**
     * @param PhabricatorDestructionEngine $engine
     * @param ActiveRecordPHID $object
     * @return mixed|void
     * @throws Throwable
     * @author 陈妙威
     */
    public function destroyObject(
        PhabricatorDestructionEngine $engine,
        $object)
    {
        $tasks = PhabricatorWorkerActiveTask::find()
            ->andWhere([
                'object_phid' => $object->getPHID()
            ])
            ->all();

        foreach ($tasks as $task) {
            $task->archiveTask(
                PhabricatorWorkerArchiveTask::RESULT_CANCELLED,
                0);
        }
    }

}
