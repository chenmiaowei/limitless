<?php

namespace orangins\modules\search\config;

use PhutilJSON;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use Yii;

/**
 * Class PhabricatorPolicyConfigOptions
 * @package orangins\modules\policy\config
 * @author 陈妙威
 */
final class PhabricatorSearchConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return Yii::t("app", 'Search');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return Yii::t("app", 'Options Search.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-search';
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

        return array(
            $this->newOption('search.main-menu', 'bool', false)
                ->setBoolOptions(
                    array(
                        Yii::t("app", 'Allow Public Visibility'),
                        Yii::t("app", 'Require Login'),
                    ))
                ->setSummary(Yii::t("app", 'Allow users to set object visibility to public.'))
                ->setDescription(
                    Yii::t("app",
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
        );
    }

}
