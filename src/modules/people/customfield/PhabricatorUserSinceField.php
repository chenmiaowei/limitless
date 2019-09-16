<?php

namespace orangins\modules\people\customfield;

use orangins\lib\helpers\OranginsViewUtil;

/**
 * Class PhabricatorUserSinceField
 * @package orangins\modules\people\customfield
 * @author 陈妙威
 */
final class PhabricatorUserSinceField
    extends PhabricatorUserCustomField
{

    /**
     * @var
     */
    private $value;

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldKey()
    {
        return 'user:since';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldName()
    {
        return \Yii::t("app", 'User Since');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getFieldDescription()
    {
        return \Yii::t("app", 'Shows user join date.');
    }

    /**
     * @return array|bool
     * @author 陈妙威
     */
    public function shouldAppearInPropertyView()
    {
        return true;
    }

    /**
     * @param array $handles
     * @return mixed|\PhutilSafeHTML
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $absolute = OranginsViewUtil::phabricator_datetime(
            $this->getObject()->created_at,
            $this->getViewer());

        $relative = phutil_format_relative_time_detailed(
            time() - $this->getObject()->created_at,
            $levels = 2);

        return hsprintf('%s (%s)', $absolute, $relative);
    }

}
