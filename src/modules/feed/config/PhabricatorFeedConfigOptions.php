<?php

namespace orangins\modules\feed\config;

use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

/**
 * Class PhabricatorFeedConfigOptions
 * @package orangins\modules\feed\config
 * @author 陈妙威
 */
final class PhabricatorFeedConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Feed');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app", 'Feed options.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-newspaper-o';
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
            $this->newOption('feed.http-hooks', 'list<string>', array())
                ->setLocked(true)
                ->setSummary(\Yii::t("app", 'POST notifications of feed events.'))
                ->setDescription(
                    \Yii::t("app",
                        "If you set this to a list of HTTP URIs, when a feed story is " .
                        "published a task will be created for each URI that posts the " .
                        "story data to the URI. Daemons automagically retry failures 100 " .
                        "times, waiting `\$fail_count * 60s` between each subsequent " .
                        "failure. Be sure to keep the daemon console (`%s`) open " .
                        "while developing and testing your end points. You may need to" .
                        "restart your daemons to start sending HTTP requests.\n\n" .
                        "NOTE: URIs are not validated, the URI must return HTTP status " .
                        "200 within 30 seconds, and no permission checks are performed.",
                        '/daemon/')),
        );
    }
}
