<?php

namespace orangins\modules\people\editors;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\xaction\PhabricatorUserDisableTransaction;
use orangins\modules\transactions\editfield\PhabricatorBoolEditField;

/**
 * Class PhabricatorUserEditEngine
 * @package orangins\modules\people\editors
 * @author 陈妙威
 */
final class PhabricatorUserEditEngine
    extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'people.user';

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app", 'Users');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return \Yii::t("app", 'Configure User Forms');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app", 'Configure creation and editing forms for users.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return new PhabricatorUser();
    }

    /**
     * @return \orangins\modules\people\query\PhabricatorPeopleQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        return PhabricatorUser::find();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app", 'Create New User');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app", 'Edit User: %s', $object->getUsername());
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return $object->getMonogram();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return \Yii::t("app", 'Create User');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app", 'User');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $object->getURI();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getCreateNewObjectPolicy()
    {
        // At least for now, forbid creating new users via EditEngine. This is
        // primarily enforcing that "user.edit" can not create users via the API.
        return PhabricatorPolicies::POLICY_NOONE;
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        return array(
            (new PhabricatorBoolEditField())
                ->setKey('disabled')
                ->setOptions(\Yii::t("app", 'Active'), \Yii::t("app", 'Disabled'))
                ->setLabel(\Yii::t("app", 'Disabled'))
                ->setDescription(\Yii::t("app", 'Disable the user.'))
                ->setTransactionType(PhabricatorUserDisableTransaction::TRANSACTIONTYPE)
                ->setIsFormField(false)
                ->setConduitDescription(\Yii::t("app", 'Disable or enable the user.'))
                ->setConduitTypeDescription(\Yii::t("app", 'True to disable the user.'))
                ->setValue($object->getIsDisabled()),
        );
    }

}
