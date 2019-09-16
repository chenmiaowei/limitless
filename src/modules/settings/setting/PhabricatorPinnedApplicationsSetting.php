<?php

namespace orangins\modules\settings\setting;

use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorPinnedApplicationsSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorPinnedApplicationsSetting
    extends PhabricatorInternalSetting
{

    /**
     *
     */
    const SETTINGKEY = 'app-pinned';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Pinned Applications');
    }

    /**
     * @return array|null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        $viewer = PhabricatorUser::getOmnipotentUser();

        $applications = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withInstalled(true)
            ->withUnlisted(false)
            ->withLaunchable(true)
            ->execute();

        $pinned = array();
        foreach ($applications as $application) {
            if ($application->isPinnedByDefault($viewer)) {
                $pinned[] = get_class($application);
            }
        }

        return $pinned;
    }
}
