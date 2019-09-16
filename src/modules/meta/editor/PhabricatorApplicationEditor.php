<?php

namespace orangins\modules\meta\editor;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\meta\application\PhabricatorApplicationsApplication;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;

/**
 * Class PhabricatorApplicationEditor
 * @package orangins\modules\meta\editor
 * @author 陈妙威
 */
final class PhabricatorApplicationEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorApplicationsApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app",'Application');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function supportsSearch()
    {
        return true;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();
        $types[] = PhabricatorTransactions::TYPE_VIEW_POLICY;
        $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     * @author 陈妙威
     */
    protected function shouldSendMail(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return false;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     * @author 陈妙威
     */
    protected function shouldPublishFeedStory(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return true;
    }

    /**
     * @param ActiveRecordPHID $object
     * @return array|void
     * @author 陈妙威
     */
    protected function getMailTo(ActiveRecordPHID $object)
    {
        return array();
    }

    /**
     * @param ActiveRecordPHID $object
     * @return array|mixed[]
     * @author 陈妙威
     */
    protected function getMailCC(ActiveRecordPHID $object)
    {
        return array();
    }

}
