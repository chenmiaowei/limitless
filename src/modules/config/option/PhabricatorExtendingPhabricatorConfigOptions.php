<?php

namespace orangins\modules\config\option;

/**
 * Class PhabricatorExtendingPhabricatorConfigOptions
 * @package orangins\modules\config\option
 * @author 陈妙威
 */
final class PhabricatorExtendingPhabricatorConfigOptions extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Extending Phabricator');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app",'Make Phabricator even cooler!');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-rocket';
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
            $this->newOption('load-libraries', 'list<string>', array())
                ->setLocked(true)
                ->setSummary(\Yii::t("app",'Paths to additional phutil libraries to load.'))
                ->addExample('/srv/our-libs/sekrit-phutil', \Yii::t("app",'Valid Setting')),
            $this->newOption('events.listeners', 'list<string>', array())
                ->setLocked(true)
                ->setSummary(
                    \Yii::t("app",'Listeners receive callbacks when interesting things occur.'))
                ->setDescription(
                    \Yii::t("app",
                        'You can respond to various application events by installing ' .
                        'listeners, which will receive callbacks when interesting things ' .
                        'occur. Specify a list of classes which extend ' .
                        'PhabricatorEventListener here.'))
                ->addExample('MyEventListener', \Yii::t("app",'Valid Setting')),
            $this->newOption(
                'aphront.default-application-configuration-class',
                'class',
                'AphrontDefaultApplicationConfiguration')
                ->setLocked(true)
                ->setBaseClass('AphrontApplicationConfiguration')
                // TODO: This could probably use some better documentation.
                ->setDescription(\Yii::t("app",'Application configuration class.')),
        );
    }

}
