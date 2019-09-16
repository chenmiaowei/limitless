<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/8
 * Time: 4:48 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\file\editors;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\file\application\PhabricatorFilesApplication;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use Yii;

/**
 * Class FileEditor
 * @package orangins\modules\file\editors
 * @author 陈妙威
 */
class PhabricatorFileEditor extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

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
        return PhabricatorFilesApplication::class;
    }

    /**
     * Get a description of the objects this editor edits, like "Differential
     * Revisions".
     *
     * @return string Human readable description of edited objects.
     */
    public function getEditorObjectsDescription()
    {
        return Yii::t('app', 'Files');
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
        return pht('[File]');
    }

    /**
     * @param ActiveRecordPHID|PhabricatorFile $object
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