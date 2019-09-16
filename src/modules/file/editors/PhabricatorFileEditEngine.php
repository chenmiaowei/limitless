<?php

namespace orangins\modules\file\editors;

use orangins\lib\db\ActiveRecord;
use orangins\modules\file\application\PhabricatorFilesApplication;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\xaction\PhabricatorFileNameTransaction;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFileEditEngine
 * @author 陈妙威
 */
final class PhabricatorFileEditEngine extends PhabricatorEditEngine
{
    /**
     *
     */
    const ENGINECONST = 'files.file';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app", 'Files');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function supportsEditEngineConfiguration()
    {
        return false;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCreateNewObjectPolicy()
    {
        // TODO: For now, this EditEngine can only edit objects, since there is
        // a lot of complexity in dealing with file data during file creation.
        return PhabricatorPolicies::POLICY_NOONE;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return \Yii::t("app", 'Configure Files Forms');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app", 'Configure creation and editing forms in Files.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorFilesApplication::class;
    }

    /**
     * @return object
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return PhabricatorFile::initializeNewFile();
    }

    /**
     * @return \orangins\modules\file\models\PhabricatorFileQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        $query = PhabricatorFile::find();
        $query->withIsDeleted(false);
        return $query;
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app", 'Create New File');
    }

    /**
     * @param PhabricatorFile $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app", 'Edit File: {0}', [$object->name]);
    }

    /**
     * @param PhabricatorFile $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return $object->getMonogram();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return \Yii::t("app", 'Create File');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app", 'File');
    }

    /**
     * @param PhabricatorApplicationTransactionEditor|ActiveRecord $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $object->getURI();
    }

    /**
     * @param $object
     * @return array|\orangins\modules\widgets\ActiveField[]
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        return array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app", 'Name'))
                ->setTransactionType(PhabricatorFileNameTransaction::TRANSACTIONTYPE)
                ->setDescription(\Yii::t("app", 'The name of the file.'))
                ->setConduitDescription(\Yii::t("app", 'Rename the file.'))
                ->setConduitTypeDescription(\Yii::t("app", 'New file name.'))
                ->setValue(ArrayHelper::getValue($object, 'name')),
        );
    }
}
