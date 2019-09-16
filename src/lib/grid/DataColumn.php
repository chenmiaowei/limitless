<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/5
 * Time: 1:50 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\grid;

/**
 * Class DataColumn
 * @package orangins\lib\grid
 * @author 陈妙威
 */
class DataColumn extends \yii\grid\DataColumn
{
    /**
     * @var array
     */
    public $sortLinkOptions = [
        'class' => 'text-white  d-block sorting'
    ];
}