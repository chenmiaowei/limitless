<?php

namespace orangins\modules\auth\systemaction;

use orangins\modules\system\systemaction\PhabricatorSystemAction;

/**
 * Class PhabricatorAuthChangePasswordAction
 * @package orangins\modules\settings\systemaction
 * @author 陈妙威
 */
final class PhabricatorAuthChangePasswordAction
    extends PhabricatorSystemAction
{

    /**
     *
     */
    const TYPECONST = 'auth.password';

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
        return 20 / phutil_units('1 hour in seconds');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLimitExplanation()
    {
        return \Yii::t("app",
            'You have failed to enter the correct account password too often in ' .
            'a short period of time.');
    }

}
