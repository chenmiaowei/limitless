<?php

namespace orangins\modules\people\menuitem;

use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleManageProfileMenuItem
 * @package orangins\modules\people\menuitem
 * @author 陈妙威
 */
final class PhabricatorPeopleManageProfileMenuItem
    extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'people.manage';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app",'Manage User');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return \Yii::t("app",'Manage');
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
    public function buildEditEngineFields(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app",'Name'))
                ->setPlaceholder($this->getDefaultName())
                ->setValue($config->getMenuItemProperty('name')),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed
     * @author 陈妙威
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    protected function newMenuItemViewList(
        PhabricatorProfileMenuItemConfiguration $config)
    {

        $user = $config->getProfileObject();
        $id = $user->getID();

        $item = $this->newItemView()
            ->setURI(Url::to(["/people/index/manage", "id" => $id]))
            ->setName($this->getDisplayName($config))
            ->setIcon('fa-gears');

        return array(
            $item,
        );
    }

}
