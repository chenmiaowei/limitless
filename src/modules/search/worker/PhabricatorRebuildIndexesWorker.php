<?php

namespace orangins\modules\search\worker;

use Exception;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorRebuildIndexesWorker
 * @package orangins\modules\search\worker
 * @author 陈妙威
 */
final class PhabricatorRebuildIndexesWorker extends PhabricatorWorker
{

    /**
     * @param $query_class
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public static function rebuildObjectsWithQuery($query_class)
    {
        parent::scheduleTask(
            __CLASS__,
            array(
                'queryClass' => $query_class,
            ),
            array(
                'priority' => parent::PRIORITY_INDEX,
            ));
    }

    /**
     * @return mixed|void
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    protected function doWork()
    {
        $viewer = PhabricatorUser::getOmnipotentUser();

        $data = $this->getTaskData();
        $query_class = idx($data, 'queryClass');

        try {
            $query = newv($query_class, array());
        } catch (Exception $ex) {
            throw new PhabricatorWorkerPermanentFailureException(
                pht(
                    'Unable to instantiate query class "%s": %s',
                    $query_class,
                    $ex->getMessage()));
        }

        $query->setViewer($viewer);

        $iterator = new PhabricatorQueryIterator($query);
        foreach ($iterator as $object) {
            PhabricatorSearchWorker::queueDocumentForIndexing(
                $object->getPHID(),
                array(
                    'force' => true,
                ));
        }
    }

}
