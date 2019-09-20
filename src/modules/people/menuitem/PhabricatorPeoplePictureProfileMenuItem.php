<?php

namespace orangins\modules\people\menuitem;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use yii\helpers\Url;

/**
 * Class PhabricatorPeoplePictureProfileMenuItem
 * @package orangins\modules\people\menuitem
 * @author 陈妙威
 */
final class PhabricatorPeoplePictureProfileMenuItem
    extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'people.picture';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'User Picture');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return \Yii::t("app", 'User Picture');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canHideMenuItem(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return false;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDisplayName(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return $this->getDefaultName();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威
     */
    public function buildEditEngineFields(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return array();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    protected function newMenuItemViewList(
        PhabricatorProfileMenuItemConfiguration $config)
    {

        $user = $config->getProfileObject();
        $picture = $user->getProfileImageURI();

        $item = $this
            ->newItemView()
            ->setDisabled($user->getIsDisabled());

        $item->newProfileImage($picture);

        return array(
            $item,
        );
    }

}
