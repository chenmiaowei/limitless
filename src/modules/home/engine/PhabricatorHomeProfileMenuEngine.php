<?php

namespace orangins\modules\home\engine;

use orangins\lib\PhabricatorApplication;
use orangins\modules\file\view\PhabricatorGlobalUploadTargetView;
use orangins\modules\home\constants\PhabricatorHomeConstants;
use orangins\modules\home\menuitem\PhabricatorHomeLauncherProfileMenuItem;
use orangins\modules\home\menuitem\PhabricatorHomeProfileMenuItem;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\search\engine\PhabricatorProfileMenuEngine;
use orangins\modules\search\menuitems\PhabricatorApplicationProfileMenuItem;
use orangins\modules\search\menuitems\PhabricatorLabelProfileMenuItem;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorHomeProfileMenuEngine
 * @package orangins\modules\home\engine
 * @author 陈妙威
 */
final class PhabricatorHomeProfileMenuEngine extends PhabricatorProfileMenuEngine
{

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function isMenuEngineConfigurable()
    {
        return true;
    }

    /**
     * @param $params
     * @return string
     * @author 陈妙威
     */
    public function getItemURI($params)
    {
        return Url::to(ArrayHelper::merge(['/home/index/index'], $params));
//        return Url::to(['/home/index/index', 'itemAction' => $itemAction, 'itemEditMode' => $mode, "itemKey" => $itemKey]);
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $item
     * @return array
     * @author 陈妙威
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    protected function buildItemViewContent(PhabricatorProfileMenuItemConfiguration $item)
    {
        $viewer = $this->getViewer();

        // Add content to the document so that you can drag-and-drop files onto
        // the home page or any home dashboard to upload them.

        $upload = (new PhabricatorGlobalUploadTargetView())
            ->setUser($viewer);

        $content = parent::buildItemViewContent($item);

        return array(
            $content,
            $upload,
        );
    }

    /**
     * @param $object
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getBuiltinProfileItems($object)
    {
        $viewer = $this->getViewer();
        $items = array();
        $custom_phid = $this->getCustomPHID();


        // Default Home Dashboard
        $items[] = $this->newItem()
            ->setBuiltinKey(PhabricatorHomeConstants::ITEM_HOME)
            ->setMenuItemKey(PhabricatorHomeProfileMenuItem::MENUITEMKEY);

        $items[] = $this->newItem()
            ->setBuiltinKey(PhabricatorHomeConstants::ITEM_APPS_LABEL)
            ->setMenuItemKey(PhabricatorLabelProfileMenuItem::MENUITEMKEY)
            ->setMenuItemProperties(array('name' => \Yii::t("app",'Favorites')));


        /** @var PhabricatorApplication[] $applications */
        $applications = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withInstalled(true)
            ->withUnlisted(false)
            ->withLaunchable(true)
            ->execute();
        foreach ($applications as $application) {
            if (!$application->isPinnedByDefault($viewer)) {
                continue;
            }

            $properties = array(
                'name' => '',
                'application' => $application->getPHID(),
            );
            $items[] = $this->newItem()
                ->setBuiltinKey($application->getPHID())
                ->setMenuItemKey(PhabricatorApplicationProfileMenuItem::MENUITEMKEY)
                ->setMenuItemProperties($properties);
        }


        $phabricatorApplicationProfileMenuItems = PhabricatorProfileMenuItem::getAllMenuItems();
        foreach ($phabricatorApplicationProfileMenuItems as $phabricatorApplicationProfileMenuItem) {
            if ($phabricatorApplicationProfileMenuItem->isPinnedByDefault()) {
                $items[] = $this->newItem()
                    ->setBuiltinKey("home." . $phabricatorApplicationProfileMenuItem->getPhobjectClassConstant('MENUITEMKEY'))
                    ->setMenuItemKey($phabricatorApplicationProfileMenuItem->getPhobjectClassConstant('MENUITEMKEY'));

            }
        }
        // Hotlink to More Applications Launcher...
//        $items[] = $this->newItem()
//            ->setBuiltinKey(PhabricatorHomeConstants::ITEM_LAUNCHER)
//            ->setMenuItemKey(PhabricatorHomeLauncherProfileMenuItem::MENUITEMKEY);

        $items[] = $this->newManageItem();

        return $items;
    }

}
