<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/17
 * Time: 11:05 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\assets;

use Yii;

/**
 * Class AssetManager
 * @package orangins\lib\assets
 * @author 陈妙威
 */
class AssetManager extends \yii\web\AssetManager
{
    /**
     * @param string $path
     * @return mixed|string
     * @author 陈妙威
     */
    protected function hash($path)
    {
        if (is_callable($this->hashCallback)) {
            return call_user_func($this->hashCallback, $path);
        }
        $path = (is_file($path) ? dirname($path) : $path);
        return sprintf('%x', crc32($path . Yii::getVersion() . '|' . $this->linkAssets));
    }
}