<?php

namespace orangins\modules\herald\editors;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use Yii;

/**
 * Class FileEditor
 * @package orangins\modules\herald\editors
 */
class HeraldRuleEditor extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     */
    public function getTransactionTypes() {
        $types = parent::getTransactionTypes();

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
        return \orangins\modules\herald\application\PhabricatorHeraldApplication::className();
    }

    /**
     * Get a description of the objects this editor edits, like "Differential
     * Revisions".
     *
     * @return string Human readable description of edited objects.
     */
    public function getEditorObjectsDescription()
    {
        return Yii::t('app', 'Herald Rule');
    }


    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     */
    protected function shouldPublishFeedStory(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function supportsSearch()
    {
        return true;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     */
    protected function shouldSendMail(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getMailSubjectPrefix()
    {
        return Yii::t('app', 'Herald Rule');
    }

    /**
     * @param ActiveRecordPHID $object
     * @return array
     * @throws \PhutilInvalidStateException
     */
    protected function getMailTo(ActiveRecordPHID $object)
    {
        return array(
            $this->requireActor()->getPHID(),
        );
    }

    /**
     * @param ActiveRecordPHID $object
     * @return array|mixed[]
     */
    protected function getMailCC(ActiveRecordPHID $object)
    {
        return array();
    }
}

