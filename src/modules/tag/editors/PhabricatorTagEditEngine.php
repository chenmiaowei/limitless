<?php

namespace orangins\modules\tag\editors;

use orangins\lib\db\ActiveRecord;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\tag\application\PhabricatorTagsApplication;
use orangins\modules\tag\datasource\PhabricatorTagDataSourceType;
use orangins\modules\tag\models\PhabricatorTag;
use orangins\modules\tag\xaction\PhabricatorTagNameTransaction;
use orangins\modules\tag\xaction\PhabricatorTagTypeTransaction;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorSelectEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorTagEditEngine
 * @author 陈妙威
 */
final class PhabricatorTagEditEngine extends PhabricatorEditEngine
{
    /**
     *
     */
    const ENGINECONST = 'tags.tag';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app", 'Tags');
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
        // a lot of complexity in dealing with tag data during tag creation.
        return PhabricatorPolicies::POLICY_USER;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return \Yii::t("app", 'Configure Tag Forms');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app", 'Configure creation and editing forms in tags.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorTagsApplication::className();
    }

    /**
     * @return object
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return PhabricatorTag::initializeNewTag($this->getViewer());
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        $query = PhabricatorTag::find();
        return $query;
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app", 'Create New Tag');
    }

    /**
     * @param PhabricatorTag $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app", 'Edit Tag: {0}', [$object->name]);
    }

    /**
     * @param PhabricatorTag $object
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
        return \Yii::t("app", 'Create Tag');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app", 'Tag');
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
                ->setTransactionType(PhabricatorTagNameTransaction::TRANSACTIONTYPE)
                ->setDescription(\Yii::t("app", 'The name of the tag.'))
                ->setConduitDescription(\Yii::t("app", 'Rename the tag.'))
                ->setConduitTypeDescription(\Yii::t("app", 'New tag name.'))
                ->setValue(ArrayHelper::getValue($object, 'name')),
            (new PhabricatorSelectEditField())
                ->setKey('type')
                ->setLabel(\Yii::t("app",'Type'))
                ->setTransactionType(
                    PhabricatorTagTypeTransaction::TRANSACTIONTYPE)
                ->setOptions(ArrayHelper::map(PhabricatorTagDataSourceType::getAllTypes(), function (PhabricatorTagDataSourceType $tagDataSourceType) {
                    return $tagDataSourceType->getClassShortName();
                }, function (PhabricatorTagDataSourceType $tagDataSourceType) {
                    return $tagDataSourceType->getName();
                }))
                ->setDescription(\Yii::t("app",'Active or archived status.'))
                ->setConduitDescription(\Yii::t("app",'Active or archive the paste.'))
                ->setConduitTypeDescription(\Yii::t("app",'New paste status constant.'))
                ->setValue(ArrayHelper::getValue($object, 'type')),
        );
    }
}
