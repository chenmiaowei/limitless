<?php

namespace orangins\modules\home\menuitem;

use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\Url;

/**
 * Class PhabricatorHomeLauncherProfileMenuItem
 * @package orangins\modules\home\menuitem
 * @author 陈妙威
 */
final class PhabricatorHomeLauncherProfileMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'home.launcher.menu';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'More Applications');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return \Yii::t("app", 'More Applications');
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
    public function canMakeDefault(PhabricatorProfileMenuItemConfiguration $config)
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
        $name = $this->getDisplayName($config);
        $icon = 'fa-globe';
        $href = Url::to(['/meta/index/query']);

        $item = $this->newItemView()
            ->setURI($href)
            ->setName($name)
            ->setIcon($icon);

        if(!$this->getViewer()->getIsAdmin()) return array();
        return array(
            $item,
        );
    }
}
