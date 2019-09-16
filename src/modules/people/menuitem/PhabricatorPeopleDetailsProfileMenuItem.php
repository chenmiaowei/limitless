<?php

namespace orangins\modules\people\menuitem;

use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleDetailsProfileMenuItem
 * @package orangins\modules\people\menuitem
 * @author 陈妙威
 */
final class PhabricatorPeopleDetailsProfileMenuItem
    extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'people.details';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app",'User Details');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDefaultName()
    {
        return \Yii::t("app",'User Details');
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
                ->setValue($config->getMenuProperty('name')),
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
        $href = Url::to(['/people/index/view', 'username' => $user->getUsername()]);

        $item = $this->newItemView()
            ->setURI($href)
            ->setName(\Yii::t("app",'Profile'))
            ->setIcon('fa-user');

        return array(
            $item,
        );
    }

}
