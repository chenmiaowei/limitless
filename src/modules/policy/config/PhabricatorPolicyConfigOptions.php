<?php

namespace orangins\modules\policy\config;

use PhutilJSON;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

/**
 * Class PhabricatorPolicyConfigOptions
 * @package orangins\modules\policy\config
 * @author 陈妙威
 */
final class PhabricatorPolicyConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Policy');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app", 'Options relating to object visibility.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-lock';
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
     */
    public function getOptions()
    {
        $policy_locked_type = 'custom:PolicyLockOptionType';
        $policy_locked_example = array(
            'people.create.users' => 'admin',
        );
        $json = new PhutilJSON();
        $policy_locked_example = $json->encodeFormatted($policy_locked_example);

        return array(
            $this->newOption('policy.allow-public', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t("app", 'Allow Public Visibility'),
                        \Yii::t("app", 'Require Login'),
                    ))
                ->setSummary(\Yii::t("app", 'Allow users to set object visibility to public.'))
                ->setDescription(
                    \Yii::t("app",
                        "Phabricator allows you to set the visibility of objects (like " .
                        "repositories and tasks) to 'Public', which means **anyone " .
                        "on the internet can see them, without needing to log in or " .
                        "have an account**." .
                        "\n\n" .
                        "This is intended for open source projects. Many installs will " .
                        "never want to make anything public, so this policy is disabled " .
                        "by default. You can enable it here, which will let you set the " .
                        "policy for objects to 'Public'." .
                        "\n\n" .
                        "Enabling this setting will immediately open up some features, " .
                        "like the user directory. Anyone on the internet will be able to " .
                        "access these features." .
                        "\n\n" .
                        "With this setting disabled, the 'Public' policy is not " .
                        "available, and the most open policy is 'All Users' (which means " .
                        "users must have accounts and be logged in to view things).")),
            $this->newOption('policy.locked', $policy_locked_type, array())
                ->setLocked(true)
                ->setSummary(\Yii::t("app",
                    'Lock specific application policies so they can not be edited.'))
                ->setDescription(\Yii::t("app",
                    'Phabricator has application policies which can dictate whether ' .
                    'users can take certain actions, such as creating new users. ' . "\n\n" .
                    'This setting allows for "locking" these policies such that no ' .
                    'further edits can be made on a per-policy basis.'))
                ->addExample(
                    $policy_locked_example,
                    \Yii::t("app", 'Lock Create User Policy To Admins')),
        );
    }

}
