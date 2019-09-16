<?php

namespace orangins\modules\file\xaction;

use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\file\models\PhabricatorFile;

/**
 * Class PhabricatorFileDeleteTransaction
 * @package orangins\modules\file\xaction
 * @author 陈妙威
 */
final class PhabricatorFileDeleteTransaction extends PhabricatorFileTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'file:delete';

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return PhabricatorFile::STATUS_ACTIVE;
    }

    /**
     * @param PhabricatorFile $object
     * @param $value
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \AphrontQueryException
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $file = $object;
        // Mark the file for deletion, save it, and schedule a worker to
        // sweep by later and pick it up.
        $file->setAttribute('is_deleted', 1);

        PhabricatorWorker::scheduleTask('FileDeletionWorker',
            array('objectPHID' => $file->getPHID()),
            array('priority' => PhabricatorWorker::PRIORITY_BULK));
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-ban';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getColor()
    {
        return 'red';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app", '{0} deleted this file.', [$this->renderAuthor()]);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return \Yii::t("app", '{0} deleted {1}.', [$this->renderAuthor(), $this->renderObject()]);
    }
}
