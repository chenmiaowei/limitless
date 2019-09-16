<?php

namespace orangins\modules\people\config;

use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\people\customfield\PhabricatorUserBlurbField;
use orangins\modules\people\customfield\PhabricatorUserIconField;
use orangins\modules\people\customfield\PhabricatorUserRealNameField;
use orangins\modules\people\customfield\PhabricatorUserRolesField;
use orangins\modules\people\customfield\PhabricatorUserSinceField;
use orangins\modules\people\customfield\PhabricatorUserStatusField;
use orangins\modules\people\customfield\PhabricatorUserTitleField;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorUserConfigOptions
 * @package orangins\modules\people\config
 * @author 陈妙威
 */
final class PhabricatorUserConfigOptions extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'User Profiles');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app",'User profiles configuration.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-users';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroup()
    {
        return 'apps';
    }

    /**
     * @return array|\orangins\modules\config\option\PhabricatorConfigOption[]
     * @author 陈妙威
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getOptions()
    {

        $default = array(
            (new PhabricatorUserRealNameField())->getFieldKey() => true,
            (new PhabricatorUserTitleField())->getFieldKey() => true,
            (new PhabricatorUserIconField())->getFieldKey() => true,
            (new PhabricatorUserSinceField())->getFieldKey() => true,
            (new PhabricatorUserRolesField())->getFieldKey() => true,
            (new PhabricatorUserStatusField())->getFieldKey() => true,
            (new PhabricatorUserBlurbField())->getFieldKey() => true,
        );

        foreach ($default as $key => $enabled) {
            $default[$key] = array(
                'disabled' => !$enabled,
            );
        }

        $custom_field_type = 'custom:PhabricatorCustomFieldConfigOptionType';

        return array(
            $this->newOption('user.fields', $custom_field_type, $default)
                ->setCustomData((new PhabricatorUser())->getCustomFieldBaseClass())
                ->setDescription(\Yii::t("app",'Select and reorder user profile fields.')),
            $this->newOption('user.custom-field-definitions', 'wild', array())
                ->setDescription(\Yii::t("app",'Add new simple fields to user profiles.')),
            $this->newOption('user.require-real-name', 'bool', true)
                ->setDescription(\Yii::t("app",'Always require real name for user profiles.'))
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Make real names required'),
                        \Yii::t("app",'Make real names optional'),
                    )),
        );
    }

}
