<?php

namespace orangins\modules\dashboard\engine;

use orangins\modules\search\engine\PhabricatorProfileMenuEngine;

/**
 * Class PhabricatorDashboardPortalProfileMenuEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardPortalProfileMenuEngine
    extends PhabricatorProfileMenuEngine
{

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    protected function isMenuEngineConfigurable()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function isMenuEnginePersonalizable()
    {
        return false;
    }

    /**
     * @param $path
     * @return mixed|string
     * @author 陈妙威
     */
    public function getItemURI($path)
    {
        $portal = $this->getProfileObject();

        return $portal->getURI() . $path;
    }

    /**
     * @param $object
     * @return array|\orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration[]
     * @author 陈妙威
     */
    protected function getBuiltinProfileItems($object)
    {
        $items = array();

        $items[] = $this->newDividerItem('tail');

        $items[] = $this->newManageItem();

        $items[] = $this->newItem()
            ->setMenuItemKey(PhabricatorDashboardPortalMenuItem::MENUITEMKEY)
            ->setBuiltinKey('manage')
            ->setIsTailItem(true);

        return $items;
    }

    /**
     * @param array $items
     * @return mixed
     * @author 陈妙威
     */
    protected function newNoMenuItemsView(array $items)
    {
        $object = $this->getProfileObject();
        $builtins = $this->getBuiltinProfileItems($object);

        if (count($items) <= count($builtins)) {
            return $this->newEmptyView(
                \Yii::t("app", 'New Portal'),
                \Yii::t("app", 'Use "Edit Menu" to add menu items to this portal.'));
        } else {
            return $this->newEmptyValue(
                \Yii::t("app", 'No Portal Content'),
                \Yii::t("app",
                    'None of the visible menu items in this portal can render any ' .
                    'content.'));
        }
    }

}
