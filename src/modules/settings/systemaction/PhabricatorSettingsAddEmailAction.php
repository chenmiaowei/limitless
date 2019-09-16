<?php

namespace orangins\modules\settings\systemaction;

use orangins\modules\system\systemaction\PhabricatorSystemAction;

/**
 * Class PhabricatorSettingsAddEmailAction
 * @package orangins\modules\settings\systemaction
 * @author 陈妙威
 */
final class PhabricatorSettingsAddEmailAction extends PhabricatorSystemAction
{

    /**
     *
     */
    const TYPECONST = 'email.add';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getActionConstant()
    {
        return self::TYPECONST;
    }

    /**
     * @return float|int|mixed
     * @author 陈妙威
     */
    public function getScoreThreshold()
    {
        return 6 / phutil_units('1 hour in seconds');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLimitExplanation()
    {
        return \Yii::t("app",
            'You are adding too many email addresses to your account too quickly.');
    }
}
