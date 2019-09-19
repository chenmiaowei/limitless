<?php

namespace applications\userservice\menuitem;

use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\Url;

/**
 * Class PhabricatorTaskIdentityMenuItem
 * @package applications\task\menuitem
 * @author 赵圆丽
 */
final class PhabricatorUserServiceMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'userservice.launcher';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return '用户数据服务';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return '用户数据服务';
    }


    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canMakeDefault(PhabricatorProfileMenuItemConfiguration $config)
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isPinnedByDefault()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isFavoriteByDefault()
    {
        return false;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed|string
     * @author 陈妙威
     */
    public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config)
    {
        $name = $config->getMenuItemProperty('name');

        if (strlen($name)) {
            return $name;
        }

        return $this->getDefaultName();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威
     */
    public function buildEditEngineFields(PhabricatorProfileMenuItemConfiguration $config)
    {
        return array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app", 'Name'))
                ->setPlaceholder($this->getDefaultName())
                ->setValue($config->getMenuItemProperty('name')),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {
        $item = [];
        $subitems = [];

        $subitems[] = (new PHUIListItemView())
            ->setKey('userservice-index')
            ->setName(\Yii::t("app", "服务列表"))
            ->setHref(Url::to(['/userservice/index/query']));
        $item[] = $this->newItemView()
            ->setURI('#')
            ->setSubListItems($subitems)
            ->setName($config->getMenuItemProperty('name') ? $config->getMenuItemProperty('name') : $this->getDefaultName())
            ->setIcon('fa-female');


        return $item;
    }
}
