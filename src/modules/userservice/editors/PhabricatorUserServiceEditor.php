<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/8
 * Time: 4:48 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\editors;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\userservice\application\PhabricatorUserServiceApplication;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\xaction\PhabricatorUserServiceAmountTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceAPITransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceExpireTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceStatusTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceTimesTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceUserTransaction;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use Yii;

/**
 * Class FileEditor
 * @package orangins\modules\file\editors
 * @author 陈妙威
 */
class PhabricatorUserServiceEditor extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorTransactions::TYPE_EDGE;
        $types[] = PhabricatorTransactions::TYPE_COMMENT;
        $types[] = PhabricatorTransactions::TYPE_VIEW_POLICY;
        return $types;
    }

    /**
     * Get the class name for the application this editor is a part of.
     *
     * Uninstalling the application will disable the editor.
     *
     * @return string Editor's application class name.
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorUserServiceApplication::className();
    }

    /**
     * Get a description of the objects this editor edits, like "Differential
     * Revisions".
     *
     * @return string Human readable description of edited objects.
     */
    public function getEditorObjectsDescription()
    {
        return Yii::t('app', 'Tasks');
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
     * @return bool
     * @author 陈妙威
     */
    protected function supportsSearch()
    {
        return true;
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
     * @return string
     * @author 陈妙威
     */
    protected function getMailSubjectPrefix()
    {
        return pht('[用户服务]');
    }

    /**
     * @param ActiveRecordPHID|PhabricatorUserService $object
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\UnknownPropertyException
     * @author 陈妙威
     */
    protected function getMailTo(ActiveRecordPHID $object)
    {
        return array(
            $object->getAuthorPHID(),
            $this->requireActor()->getPHID(),
        );
    }
}