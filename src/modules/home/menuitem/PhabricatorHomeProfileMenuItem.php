<?php

namespace orangins\modules\home\menuitem;

use orangins\modules\home\homeview\PhabricatorHomeViewAbstract;
use orangins\modules\home\view\PHUIHomeView;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorSelectEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorHomeProfileMenuItem
 * @package orangins\modules\home\menuitem
 * @author 陈妙威
 */
final class PhabricatorHomeProfileMenuItem
    extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'home.dashboard';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'Built-in Homepage');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return \Yii::t("app", 'Home');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canMakeDefault(
        PhabricatorProfileMenuItemConfiguration $config)
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
        $name = $config->getMenuItemProperty('name');

        if (strlen($name)) {
            return $name;
        }

        return $this->getDefaultName();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return null
     * @author 陈妙威
     */
    public function newPageContent(PhabricatorProfileMenuItemConfiguration $config)
    {
        $viewer = $this->getViewer();

        $phabricatorHomeViewAbstracts = PhabricatorHomeViewAbstract::getAllTypes();
        $name = $config->getMenuItemProperty('home_class');
        if (strlen($name) && isset($phabricatorHomeViewAbstracts[$name])) {
            $homeViewAbstract = $phabricatorHomeViewAbstracts[$name];
            $homeViewAbstract->setViewer($viewer);
            return $homeViewAbstract->render();
        } else {
            return (new PHUIHomeView())
                ->setViewer($viewer);
        }
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威

     */
    public function buildEditEngineFields(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        $phabricatorHomeViewAbstracts = PhabricatorHomeViewAbstract::getAllTypes();

        return array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app", 'Name'))
                ->setPlaceholder($this->getDefaultName())
                ->setValue($config->getMenuItemProperty('name')),
            (new PhabricatorSelectEditField())
                ->setKey('home_class')
                ->setLabel(\Yii::t("app", '首页类型'))
                ->setOptions(ArrayHelper::merge([
                    "" => "无"
                ], ArrayHelper::map($phabricatorHomeViewAbstracts, function (PhabricatorHomeViewAbstract $abstract) {
                    return $abstract->getClassShortName();
                }, function (PhabricatorHomeViewAbstract $abstract) {
                    return $abstract->getName();
                })))
                ->setValue($config->getMenuItemProperty('home_class')),
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

        $name = $this->getDisplayName($config);
        $icon = 'fa-home';
        $href = $this->getItemViewURI($config);

        $item = $this->newItemView()
            ->setURI($href)
            ->setName($name)
            ->setIcon($icon);

        return array(
            $item,
        );
    }

}
