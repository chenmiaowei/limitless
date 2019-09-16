<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use orangins\lib\infrastructure\daemon\workers\phid\PhabricatorWorkerBulkJobPHIDType;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerBulkJobTransactionQuery;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorWorkerBulkJobTransaction
 * @package orangins\lib\infrastructure\daemon\workers\storage
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobTransaction
    extends PhabricatorApplicationTransaction
{

    /**
     *
     */
    const TYPE_STATUS = 'bulkjob.status';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_bulkjobtransaction';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'worker';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorWorkerBulkJobPHIDType::TYPECONST;
    }

    /**
     * @return string
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException*@throws \Exception
     * @author 陈妙威
     */
    public function getTitle()
    {
        $author_phid = $this->getAuthorPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $type = $this->getTransactionType();
        switch ($type) {
            case self::TYPE_STATUS:
                if ($old === null) {
                    return \Yii::t("app",
                        '{0} created this bulk job.',
                        [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    switch ($new) {
                        case PhabricatorWorkerBulkJob::STATUS_WAITING:
                            return \Yii::t("app",
                                '{0} confirmed this job.',
                               [
                                   $this->renderHandleLink($author_phid)
                               ]);
                        case PhabricatorWorkerBulkJob::STATUS_RUNNING:
                            return \Yii::t("app",
                                '{0} marked this job as running.',
                                [
                                    $this->renderHandleLink($author_phid)
                                ]);
                        case PhabricatorWorkerBulkJob::STATUS_COMPLETE:
                            return \Yii::t("app",
                                '{0} marked this job complete.',
                               [
                                   $this->renderHandleLink($author_phid)
                               ]);
                    }
                }
                break;
        }

        return parent::getTitle();
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorWorkerBulkJobTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorWorkerBulkJobTransactionQuery(get_called_class());
    }
}
