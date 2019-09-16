<?php

namespace orangins\modules\config\option;

use yii\helpers\Html;

/**
 * Class PhabricatorPHDConfigOptions
 * @package orangins\modules\config\option
 * @author 陈妙威
 */
final class PhabricatorPHDConfigOptions extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Daemons');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app", 'Options relating to PHD (daemons).');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'icon-pied-piper-alt';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroup()
    {
        return 'core';
    }

    /**
     * @return array|PhabricatorConfigOption[]
     * @author 陈妙威
     */
    public function getOptions()
    {
        return array(
            $this->newOption('phd.pid-directory', 'string', '/var/tmp/phd/pid')
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app", 'Directory that phd should use to track running daemons.')),
            $this->newOption('phd.log-directory', 'string', '/var/tmp/phd/log')
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app", 'Directory that the daemons should use to store log files.')),
            $this->newOption('phd.taskmasters', 'int', 4)
                ->setLocked(true)
                ->setSummary(\Yii::t("app", 'Maximum taskmaster daemon pool size.'))
                ->setDescription(
                    \Yii::t("app",
                        'Maximum number of taskmaster daemons to run at once. Raising ' .
                        'this can increase the maximum throughput of the task queue. The ' .
                        'pool will automatically scale down when unutilized.')),
            $this->newOption('phd.verbose', 'bool', false)
                ->setLocked(true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app", 'Verbose mode'),
                        \Yii::t("app", 'Normal mode'),
                    ))
                ->setSummary(\Yii::t("app", "Launch daemons in 'verbose' mode by default."))
                ->setDescription(
                    \Yii::t("app",
                        "Launch daemons in 'verbose' mode by default. This creates a lot " .
                        "of output, but can help debug issues. Daemons launched in debug " .
                        "mode with '%s' are always launched in verbose mode. " .
                        "See also '%s'.",
                        [
                            'phd debug',
                            'phd.trace'
                        ])),
            $this->newOption('phd.user', 'string', null)
                ->setLocked(true)
                ->setSummary(\Yii::t("app", 'System user to run daemons as.'))
                ->setDescription(
                    \Yii::t("app",
                        'Specify a system user to run the daemons as. Primarily, this ' .
                        'user will own the working copies of any repositories that ' .
                        'Phabricator imports or manages. This option is new and ' .
                        'experimental.')),
            $this->newOption('phd.trace', 'bool', false)
                ->setLocked(true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app", 'Trace mode'),
                        \Yii::t("app", 'Normal mode'),
                    ))
                ->setSummary(\Yii::t("app", "Launch daemons in 'trace' mode by default."))
                ->setDescription(
                    \Yii::t("app",
                        "Launch daemons in 'trace' mode by default. This creates an " .
                        "ENORMOUS amount of output, but can help debug issues. Daemons " .
                        "launched in debug mode with '%s' are always launched in " .
                        "trace mode. See also '%s'.",
                        [
                            'phd debug',
                            'phd.verbose'
                        ])),
            $this->newOption('phd.garbage-collection', 'wild', array())
                ->setLocked(true)
                ->setLockedMessage(
                    \Yii::t("app",
                        'This option can not be edited from the web UI. Use %s to adjust ' .
                        'garbage collector policies.',
                        [
                            Html::tag('tt', 'bin/garbage set-policy', array())
                        ]))
                ->setSummary(\Yii::t("app", 'Retention policies for garbage collection.'))
                ->setDescription(
                    \Yii::t("app",
                        'Customizes retention policies for garbage collectors.')),
        );
    }

}
