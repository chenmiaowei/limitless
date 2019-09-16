<?php

namespace orangins\lib\infrastructure\daemon\workers\editor;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJobTransaction;
use orangins\modules\daemon\application\PhabricatorDaemonsApplication;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorWorkerBulkJobEditor
 * @package orangins\lib\infrastructure\daemon\workers\editor
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorDaemonsApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app", 'Bulk Jobs');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorWorkerBulkJobTransaction::TYPE_STATUS;

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return
     * @author 陈妙威
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorWorkerBulkJobTransaction::TYPE_STATUS:
                return $object->getStatus();
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed|void
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorWorkerBulkJobTransaction::TYPE_STATUS:
                return $xaction->getNewValue();
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \PhutilJSONParserException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $type = $xaction->getTransactionType();
        $new = $xaction->getNewValue();

        switch ($type) {
            case PhabricatorWorkerBulkJobTransaction::TYPE_STATUS:
                $object->setStatus($xaction->getNewValue());
                return;
        }

        return parent::applyCustomInternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return void
     * @throws \AphrontQueryException
     * @throws \PhutilJSONParserException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $type = $xaction->getTransactionType();
        $new = $xaction->getNewValue();

        switch ($type) {
            case PhabricatorWorkerBulkJobTransaction::TYPE_STATUS:
                switch ($new) {
                    case PhabricatorWorkerBulkJob::STATUS_WAITING:
                        PhabricatorWorker::scheduleTask(
                            'PhabricatorWorkerBulkJobCreateWorker',
                            array(
                                'jobID' => $object->getID(),
                            ),
                            array(
                                'priority' => PhabricatorWorker::PRIORITY_BULK,
                            ));
                        break;
                }
                return;
        }

        return parent::applyCustomExternalTransaction($object, $xaction);
    }


}
