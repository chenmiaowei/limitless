<?php

namespace orangins\modules\search\menuitems;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorIconSetEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * 主菜单-添加快速链接
 * Class PhabricatorLinkProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorLinkProfileMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'link';

    /**
     *
     */
    const FIELD_URI = 'uri';
    /**
     *
     */
    const FIELD_NAME = 'name';
    /**
     *
     */
    const FIELD_TOOLTIP = 'tooltip';

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMenuItemTypeIcon()
    {
        return 'fa-link';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'Link');
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
        return $this->getLinkName($config);
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
                ->setKey(self::FIELD_NAME)
                ->setLabel(\Yii::t("app", 'Name'))
                ->setIsRequired(true)
                ->setValue($this->getLinkName($config)),
            (new PhabricatorTextEditField())
                ->setKey(self::FIELD_URI)
                ->setLabel(\Yii::t("app", 'URI'))
                ->setIsRequired(true)
                ->setValue($this->getLinkURI($config)),
            (new PhabricatorTextEditField())
                ->setKey(self::FIELD_TOOLTIP)
                ->setLabel(\Yii::t("app", 'Tooltip'))
                ->setValue($this->getLinkTooltip($config)),
            (new PhabricatorIconSetEditField())
                ->setKey('icon')
                ->setLabel(\Yii::t("app", 'Icon'))
                ->setIconSet(new PhabricatorProfileMenuItemIconSet())
                ->setValue($this->getLinkIcon($config)),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    private function getLinkName(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return $config->getMenuItemProperty('name');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    private function getLinkIcon(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return $config->getMenuItemProperty('icon', 'link');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    private function getLinkURI(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return $config->getMenuItemProperty('uri');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    private function getLinkTooltip(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return $config->getMenuItemProperty('tooltip');
    }

    /**
     * @param $uri
     * @return mixed
     * @author 陈妙威
     */
    private function isValidLinkURI($uri)
    {
        return PhabricatorEnv::isValidURIForLink($uri);
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {

        $icon = $this->getLinkIcon($config);
        $name = $this->getLinkName($config);
        $href = $this->getLinkURI($config);
        $tooltip = $this->getLinkTooltip($config);

        if (!$this->isValidLinkURI($href)) {
            $href = '#';
        }

        $icon_object = (new PhabricatorProfileMenuItemIconSet())
            ->getIcon($icon);
        if ($icon_object) {
            $icon_class = $icon_object->getIcon();
        } else {
            $icon_class = 'fa-link';
        }

        $item = $this->newItemView()
            ->setURI($href)
            ->setName($name)
            ->setIcon($icon_class)
            ->setTooltip($tooltip)
            ->setRel('noreferrer');

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
    public function validateTransactions(
        PhabricatorProfileMenuItemConfiguration $config,
        $field_key,
        $value,
        array $xactions)
    {

        $viewer = $this->getViewer();
        $errors = array();

        if ($field_key == self::FIELD_NAME) {
            if ($this->isEmptyTransaction($value, $xactions)) {
                $errors[] = $this->newRequiredError(
                    \Yii::t("app", 'You must choose a link name.'),
                    $field_key);
            }
        }

        if ($field_key == self::FIELD_URI) {
            if ($this->isEmptyTransaction($value, $xactions)) {
                $errors[] = $this->newRequiredError(
                    \Yii::t("app", 'You must choose a URI to link to.'),
                    $field_key);
            }

            foreach ($xactions as $xaction) {
                $new = $xaction['new'];

                if (!$new) {
                    continue;
                }

                if ($new === $value) {
                    continue;
                }

                if (!$this->isValidLinkURI($new)) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'URI "%s" is not a valid link URI. It should be a full, valid ' .
                            'URI beginning with a protocol like "%s".',
                            $new,
                            'https://'),
                        $xaction['xaction']);
                }
            }
        }

        return $errors;
    }
}
