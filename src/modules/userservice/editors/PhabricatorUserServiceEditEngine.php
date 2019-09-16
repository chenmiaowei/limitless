<?php

namespace orangins\modules\userservice\editors;

use orangins\lib\db\ActiveRecord;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\userservice\application\PhabricatorUserServiceApplication;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\servicetype\PhabricatorUserServiceType;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;

/**
 * Class PhabricatorUserServiceEditEngine
 * @author 陈妙威
 */
final class PhabricatorUserServiceEditEngine extends PhabricatorEditEngine
{
    /**
     *
     */
    const ENGINECONST = 'userservices.userservice';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app", 'User Service');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function supportsEditEngineConfiguration()
    {
        return true;
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
        return \Yii::t("app", 'Configure User Service Forms');
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
        return PhabricatorUserServiceApplication::className();
    }

    /**
     * @return object
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return PhabricatorUserService::initializeNewUserService($this->getViewer());
    }

    /**
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    protected function newObjectQuery()
    {
        $query = PhabricatorUserService::find()
            ->andWhere(['status' => PhabricatorUserService::STATUS_ACTIVE]);
        return $query;
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app", 'Create New User Service');
    }

    /**
     * @param PhabricatorUserService $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app", 'Edit User Service: {0}', [$object->name]);
    }

    /**
     * @param PhabricatorUserService $object
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
        return \Yii::t("app", 'Create User Service');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app", "用户数据服务");
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
     * @return array|\orangins\modules\search\models\PhabricatorEditEngineConfiguration[]
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newBuiltinEngineConfigurations()
    {
        $wild = head(parent::newBuiltinEngineConfigurations());

        $configurations = [];
        $configurations[] = $wild;

        $types = PhabricatorUserServiceType::getAllTypes();
        foreach ($types as $type) {
            $configurations[] =  $this->newConfiguration()
                ->setBuiltinKey($type->getKey())
                ->setName($type->getName())
                ->setIsEdit(true)
                ->setFieldOrder([
                    'user_phid',
                    'type',
                ])
                ->setFieldDefault("type", $type->getClassShortName())
                ->setFieldLocks([
                    'type' => PhabricatorEditEngineConfiguration::LOCK_LOCKED
                ]);
        }
        return $configurations;
    }


    /**
     * @author 陈妙威
     * @param $object
     * @return array
     */
    protected function buildCustomEditFields($object)
    {
        return array();
    }
}
