<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 9:52 PM
 */

namespace orangins\modules\file\widgets;

use orangins\modules\file\models\PhabricatorFile;
use orangins\lib\view\AphrontView;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

/**
 * Class FileIconColumn
 * @package orangins\modules\file\widgets
 */
class FilePHUIIconView extends AphrontView
{
    /**
     * @var
     */
    public $value;

    /**
     * @var array
     */
    public $options = [
        "class" => "rounded-round",
        "width" => 40,
        "height" => 40,
    ];

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->value) {
            throw new InvalidConfigException('The "value" property must be configured as a array.');
        }
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function run()
    {
        $fileEntities = PhabricatorFile::findModelByPHID($this->value);
        return Html::a(Html::img($fileEntities->getViewURI(), $this->options), $fileEntities->getViewURI(), [
            "data-fancybox" => "image",
            "data-caption" => ""
        ]);
    }
}