<?php

namespace orangins\modules\search\menuitems;

use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * 主菜单-添加普通文字
 * Class PhabricatorLabelProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorLabelProfileMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'label';
    /**
     *
     */
    const FIELD_NAME = 'name';

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMenuItemTypeIcon()
    {
        return 'fa-map-signs';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'Label');
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canAddToObject($object)
    {
        return true;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed
     * @author 陈妙威

     */
    public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config)
    {
        return $this->getLabelName($config);
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
                ->setKey(self::FIELD_NAME)
                ->setLabel(\Yii::t("app", 'Name'))
                ->setIsRequired(true)
                ->setValue($this->getLabelName($config)),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    private function getLabelName(PhabricatorProfileMenuItemConfiguration $config)
    {
        return $config->getMenuItemProperty('name');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {

        $name = $this->getLabelName($config);

        $item = $this->newItemView()
            ->setName($name)
            ->setIsLabel(true);

        return array(
            $item,
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @param $field_key
     * @param $value
     * @param array $xactions
     * @return array
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function validateTransactions(PhabricatorProfileMenuItemConfiguration $config,
                                            $field_key,
                                            $value,
                                            array $xactions)
    {

        $viewer = $this->getViewer();
        $errors = array();

        if ($field_key == self::FIELD_NAME) {
            if ($this->isEmptyTransaction($value, $xactions)) {
                $errors[] = $this->newRequiredError(
                    \Yii::t("app", 'You must choose a label name.'),
                    $field_key);
            }
        }

        return $errors;
    }
}
