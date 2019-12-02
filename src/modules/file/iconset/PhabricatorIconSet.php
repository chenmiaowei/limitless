<?php

namespace orangins\modules\file\iconset;

use orangins\lib\OranginsObject;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUIIconView;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorIconSet
 * @package orangins\modules\file\iconset
 * @author 陈妙威
 */
abstract class PhabricatorIconSet extends OranginsObject
{

    /**
     * @return PhabricatorIconSetIcon[]
     * @author 陈妙威
     */
    abstract protected function newIcons();

    /**
     * @return string
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function getIconSetKey()
    {
        return $this->getPhobjectClassConstant('ICONSETKEY');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getChooseButtonText()
    {
        return Yii::t("app",'Choose Icon...');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSelectIconTitleText()
    {
        return Yii::t("app",'Choose Icon');
    }

    /**
     * @return string
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function getSelectURI()
    {
        $key = $this->getIconSetKey();
        return Url::to([
            "/file/index/iconset",
            "key" => $key
        ]);
    }

    /**
     * @return PhabricatorIconSetIcon[]
     * @author 陈妙威
     */
    final public function getIcons()
    {
        $icons = $this->newIcons();

        // TODO: Validate icons.
        $icons = mpull($icons, null, 'getKey');

        return $icons;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    final public function getIcon($key)
    {
        $icons = $this->getIcons();
        return ArrayHelper::getValue($icons, $key);
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    final public function getIconLabel($key)
    {
        $icon = $this->getIcon($key);

        if ($icon) {
            return $icon->getLabel();
        }

        return $key;
    }

    /**
     * @param PhabricatorIconSetIcon $icon
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    final public function renderIconForControl(PhabricatorIconSetIcon $icon)
    {
        return JavelinHtml::phutil_tag(
            'span',
            array(),
            array(
                (new PHUIIconView())->addClass("pr-2")->setIcon($icon->getIcon()),
                $icon->getLabel(),
            ));
    }

    /**
     * @param $key
     * @return PhabricatorIconSet
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getIconSetByKey($key)
    {
        $sets = self::getAllIconSets();
        return ArrayHelper::getValue($sets, $key);
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllIconSets()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getIconSetKey')
            ->execute();
    }

}
