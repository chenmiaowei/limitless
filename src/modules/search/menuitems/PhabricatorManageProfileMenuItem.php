<?php

namespace orangins\modules\search\menuitems;

use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * Class PhabricatorManageProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorManageProfileMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'menu.manage';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'Manage Menu');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return \Yii::t("app", 'Edit Menu');
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
     * @return bool
     * @author 陈妙威
     */
    public function canMakeDefault(
        PhabricatorProfileMenuItemConfiguration $config)
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
     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {
        $viewer = $this->getViewer();

        if (!$viewer->isLoggedIn()) {
            return array();
        }

        $engine = $this->getEngine();
        $uri = $engine->getItemURI([
            'itemAction' => 'configure'
        ]);

        $name = $this->getDisplayName($config);
        $icon = 'fa-pencil';

        $item = $this->newItemView()
            ->setURI($uri)
            ->setName($name)
            ->setIcon($icon);

        if(!$this->getViewer()->getIsAdmin()) return array();
        return array(
            $item,
        );
    }
}
