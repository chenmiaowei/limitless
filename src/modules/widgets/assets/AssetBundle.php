<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/17
 * Time: 5:20 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\assets;


use PhutilClassMapQuery;

/**
 * Class AssetBundle
 * @package orangins\modules\widgets\assets
 * @author 陈妙威
 */
class AssetBundle extends \yii\web\AssetBundle
{
    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function getAllAssetBundles()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->execute();
    }
}