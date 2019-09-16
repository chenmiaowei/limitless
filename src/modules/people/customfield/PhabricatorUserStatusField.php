<?php

namespace orangins\modules\people\customfield;

use orangins\lib\PhabricatorApplication;
use orangins\modules\calendar\application\PhabricatorCalendarApplication;

/**
 * Class PhabricatorUserStatusField
 * @package orangins\modules\people\customfield
 * @author 陈妙威
 */
final class PhabricatorUserStatusField
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
        return 'user:status';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldName()
    {
        return \Yii::t("app", 'Availability');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getFieldDescription()
    {
        return \Yii::t("app", 'Shows when a user is away or busy.');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInPropertyView()
    {
        return true;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function isFieldEnabled()
    {
        return PhabricatorApplication::isClassInstalled(PhabricatorCalendarApplication::class);
    }

    /**
     * @param array $handles
     * @return mixed
     * @author 陈妙威
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldDataNotAvailableException
     */
    public function renderPropertyViewValue(array $handles)
    {
        $user = $this->getObject();
        $viewer = $this->requireViewer();

        return (new PHUIUserAvailabilityView())
            ->setViewer($viewer)
            ->setAvailableUser($user);
    }
}
