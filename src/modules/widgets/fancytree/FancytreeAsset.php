<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/5
 * Time: 11:54 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\fancytree;

use orangins\modules\widgets\assets\AssetBundle;

/**
 * Asset bundle for fancytree Widget
 *
 * @author Wanderson Bragança <wanderson.wbc@gmail.com>
 */
class FancytreeAsset extends AssetBundle
{
    public $sourcePath = '@bower/fancytree';
    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset'
    ];
    /**
     * Set up CSS and JS asset arrays based on the base-file names
     * @param string $type whether 'css' or 'js'
     * @param array $files the list of 'css' or 'js' basefile names
     */
    protected function setupAssets($type, $files = [])
    {
        $srcFiles = [];
        $minFiles = [];
        foreach ($files as $file) {
            $srcFiles[] = "{$file}.{$type}";
            $minFiles[] = "{$file}.min.{$type}";
        }
        if (empty($this->$type)) {
            $this->$type = YII_DEBUG ? $srcFiles : $minFiles;
        }
    }
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('js', ['dist/jquery.fancytree-all']);
        parent::init();
    }
}