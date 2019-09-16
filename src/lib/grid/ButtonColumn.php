<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/5
 * Time: 1:22 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\grid;

use yii\base\InvalidConfigException;
use yii\grid\Column;

/**
 * Class ActionColumn
 * @package orangins\lib\grid
 * @author 陈妙威
 */
class ButtonColumn extends Column
{
    /**
     * @var string
     */
    public $header;

    /**
     * @var \Closure
     */
    public $value;

    /**
     * @var array
     */
    public $contentOptions = [
        "class" => "text-center"
    ];

    public $headerOptions = [
        "class" => "text-center"
    ];

    /**
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        if (!$this->value instanceof \Closure) {
            throw new InvalidConfigException(\Yii::t("app", 'The "value" property of "{0}" must be instance of {1}.', [
                get_called_class(),
                ' \Closure'
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        return call_user_func($this->value, $model, $key, $index, $this);
    }
}