<?php

namespace orangins\modules\people\menuitem;

use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * Class PhabricatorPeopleBadgesProfileMenuItem
 * @package orangins\modules\people\menuitem
 * @author 陈妙威
 */
final class PhabricatorPeopleBadgesProfileMenuItem
    extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'people.badges';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app",'Badges');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return \Yii::t("app",'Badges');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canHideMenuItem(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return true;
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
            ->setURI("/people/badges/{$id}/")
            ->setName($this->getDisplayName($config))
            ->setIcon('fa-trophy');

        return array(
            $item,
        );
    }

}
