<?php

namespace orangins\modules\rbac\editors;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\rbac\application\PhabricatorRBACApplication;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\rbac\xaction\PhabricatorRBACRoleDescTransaction;
use orangins\modules\rbac\xaction\PhabricatorRBACRoleNameTransaction;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorRBACRoleEditEngine
 * @author 陈妙威
 */
final class PhabricatorRBACRoleEditEngine extends PhabricatorEditEngine
{
    /**
     *
     */
    const ENGINECONST = 'rbac.role';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app", '角色');
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
        return \Yii::t("app", '创建角色');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app", 'Configure creation and editing forms in Role.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorRBACApplication::className();
    }

    /**
     * @return object
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return RbacRole::initializeNewRole($this->getViewer());
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|\orangins\modules\rbac\models\PhabricatorRBACRoleQuery
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        $query = RbacRole::find();
        return $query;
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app", 'Create New Role');
    }

    /**
     * @param RbacRole $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app", 'Edit Role: {0}', [$object->name]);
    }

    /**
     * @param RbacRole $object
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
        return \Yii::t("app", 'Create Role');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app", 'Role');
    }

    /**
     * @param RbacRole $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $object->getURI();
    }

    /**
     * @param ActiveRecordPHID $object
     * @author 陈妙威
     * @return array
     */
    protected function buildCustomEditFields($object)
    {
        $isNewRecord = $object->isNewRecord;
        return [
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app", 'Name'))
                ->setTransactionType(PhabricatorRBACRoleNameTransaction::TRANSACTIONTYPE)
                ->setDescription(\Yii::t("app", 'The name of the role.'))
                ->setConduitDescription(\Yii::t("app", 'Rename the role.'))
                ->setConduitTypeDescription(\Yii::t("app", 'New role name.'))
                ->setIsHidden(!$isNewRecord)
                ->setValue(ArrayHelper::getValue($object, 'name')),
            (new PhabricatorTextEditField())
                ->setKey('description')
                ->setLabel(\Yii::t("app",'Description'))
                ->setTransactionType(PhabricatorRBACRoleDescTransaction::TRANSACTIONTYPE)
                ->setDescription(\Yii::t("app",'Active or archived status.'))
                ->setConduitDescription(\Yii::t("app",'Active or archive the paste.'))
                ->setConduitTypeDescription(\Yii::t("app",'New paste status constant.'))
                ->setValue(ArrayHelper::getValue($object, 'description')),
        ];
    }
}
