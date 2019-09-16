<?php

namespace orangins\modules\favorites\engine;

use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\search\engine\PhabricatorProfileMenuEngine;
use orangins\modules\search\menuitems\PhabricatorEditEngineProfileMenuItem;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorFavoritesProfileMenuEngine
 * @package orangins\modules\favorites\engine
 * @author 陈妙威
 */
final class PhabricatorFavoritesProfileMenuEngine
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
     * @param $params
     * @return mixed|string
     * @author 陈妙威
     */
    public function getItemURI($params)
    {
        return Url::to(ArrayHelper::merge(['/favorites/index/index'], $params));
    }

    /**
     * @param $object
     * @return array|\orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration[]
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getBuiltinProfileItems($object)
    {
        $items = array();

        $engines = PhabricatorEditEngine::getAllEditEngines();
        /** @var PhabricatorEditEngine[] $engines */
        $engines = msortv($engines, 'getQuickCreateOrderVector');

        foreach ($engines as $engine) {
            foreach ($engine->getDefaultQuickCreateFormKeys() as $form_key) {
                $form_hash = PhabricatorHash::digestForIndex($form_key);
                $builtin_key = "editengine.form({$form_hash})";

                $properties = array(
                    'name' => null,
                    'formKey' => $form_key,
                );

                $items[] = $this->newItem()
                    ->setBuiltinKey($builtin_key)
                    ->setMenuItemKey(PhabricatorEditEngineProfileMenuItem::MENUITEMKEY)
                    ->setMenuItemProperties($properties);
            }
        }

        $phabricatorApplicationProfileMenuItems = PhabricatorProfileMenuItem::getAllMenuItems();
        foreach ($phabricatorApplicationProfileMenuItems as $phabricatorApplicationProfileMenuItem) {
            if ($phabricatorApplicationProfileMenuItem->isFavoriteByDefault()) {
                $items[] = $this->newItem()
                    ->setBuiltinKey("home." . $phabricatorApplicationProfileMenuItem->getPhobjectClassConstant('MENUITEMKEY'))
                    ->setMenuItemKey($phabricatorApplicationProfileMenuItem->getPhobjectClassConstant('MENUITEMKEY'));

            }
        }

//        $items[] = $this->newDividerItem('tail');
//        $items[] = $this->newManageItem()
//            ->setMenuItemProperty('name', pht('Edit Favorites'));
//
        return $items;
    }

}
