<?php

namespace orangins\modules\search\menuitems;

use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorInstructionsEditField;

/**
 * 主菜单-添加分隔符
 * Class PhabricatorDividerProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorDividerProfileMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'divider';

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMenuItemTypeIcon()
    {
        return 'fa-minus';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'Divider');
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canAddToObject($object)
    {
        return true;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config)
    {
        return \Yii::t("app", "\xE2\x80\x94");
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威
     */
    public function buildEditEngineFields(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return array(
            (new PhabricatorInstructionsEditField())
                ->setValue(
                    \Yii::t("app",
                        'This is a visual divider which you can use to separate ' .
                        'sections in the menu. It does not have any configurable ' .
                        'options.')),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed
     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {
        $item = $this->newItemView()
            ->setIsDivider(true);
        return array(
            $item,
        );
    }
}
